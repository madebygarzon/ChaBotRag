<?php
/**
 * Embeddings Service
 *
 * @package AIChatbotRAG
 */

namespace AIChatbotRAG\Services;

use AIChatbotRAG\Database;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class EmbeddingsService
 * Handles embeddings generation and similarity search
 *
 * Note: For MVP, we use simple keyword matching and TF-IDF scoring.
 * This architecture is prepared to migrate to proper embeddings with external vector DB.
 */
class EmbeddingsService {

    private string $embeddingModel;

    public function __construct() {
        $this->embeddingModel = get_option('ai_chatbot_rag_embedding_model', 'keyword-tfidf');
    }

    /**
     * Generate embeddings for all chunks
     */
    public function generateAllEmbeddings(): array {
        global $wpdb;

        $chunksTable = Database::getTableName(Database::TABLE_CHUNKS);
        $embeddingsTable = Database::getTableName(Database::TABLE_EMBEDDINGS);

        $chunks = $wpdb->get_results("SELECT id, content_clean FROM {$chunksTable}");

        $stats = [
            'total_chunks' => count($chunks),
            'processed' => 0,
            'errors' => [],
        ];

        foreach ($chunks as $chunk) {
            try {
                // Check if embedding already exists
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$embeddingsTable} WHERE chunk_id = %d AND embedding_model = %s",
                    $chunk->id,
                    $this->embeddingModel
                ));

                if ($exists > 0) {
                    continue; // Skip if already exists
                }

                $embedding = $this->generateEmbedding($chunk->content_clean);

                $wpdb->insert(
                    $embeddingsTable,
                    [
                        'chunk_id' => $chunk->id,
                        'embedding_model' => $this->embeddingModel,
                        'embedding' => wp_json_encode($embedding),
                        'dimensions' => count($embedding),
                    ],
                    ['%d', '%s', '%s', '%d']
                );

                $stats['processed']++;
            } catch (\Exception $e) {
                $stats['errors'][] = sprintf(
                    'Error generating embedding for chunk %d: %s',
                    $chunk->id,
                    $e->getMessage()
                );
            }
        }

        return $stats;
    }

    /**
     * Generate embedding for text (MVP: simple keyword extraction)
     */
    private function generateEmbedding(string $text): array {
        // For MVP: Extract keywords and their frequencies (TF-IDF approach)
        $keywords = $this->extractKeywords($text);

        // Normalize to create a simple "embedding" vector
        return $keywords;
    }

    /**
     * Extract keywords from text with frequencies
     */
    private function extractKeywords(string $text, int $maxKeywords = 50): array {
        // Convert to lowercase
        $text = mb_strtolower($text, 'UTF-8');

        // Remove common Spanish stop words
        $stopWords = $this->getStopWords();

        // Extract words
        $words = preg_split('/\s+/', $text);
        $words = array_filter($words, function($word) use ($stopWords) {
            return strlen($word) > 2 && !in_array($word, $stopWords);
        });

        // Count word frequencies
        $frequencies = array_count_values($words);

        // Sort by frequency
        arsort($frequencies);

        // Get top keywords
        return array_slice($frequencies, 0, $maxKeywords, true);
    }

    /**
     * Search for relevant chunks based on query
     */
    public function searchSimilarChunks(string $query, int $limit = 5): array {
        global $wpdb;

        $chunksTable = Database::getTableName(Database::TABLE_CHUNKS);
        $embeddingsTable = Database::getTableName(Database::TABLE_EMBEDDINGS);

        // Extract keywords from query
        $queryKeywords = $this->extractKeywords($query, 20);

        if (empty($queryKeywords)) {
            return [];
        }

        // Build search query using keywords
        $searchTerms = array_keys($queryKeywords);
        $whereConditions = [];
        $prepareArgs = [];

        foreach ($searchTerms as $term) {
            $whereConditions[] = "c.content_clean LIKE %s";
            $prepareArgs[] = '%' . $wpdb->esc_like($term) . '%';
        }

        $whereClause = implode(' OR ', $whereConditions);

        $query = "
            SELECT c.*,
                   (LENGTH(c.content_clean) - LENGTH(REPLACE(LOWER(c.content_clean), %s, ''))) / LENGTH(%s) as relevance_score
            FROM {$chunksTable} c
            WHERE {$whereClause}
            ORDER BY relevance_score DESC, c.word_count DESC
            LIMIT %d
        ";

        // Add first search term for relevance scoring
        array_unshift($prepareArgs, $searchTerms[0], $searchTerms[0]);
        $prepareArgs[] = $limit;

        $results = $wpdb->get_results($wpdb->prepare($query, ...$prepareArgs));

        // Calculate better similarity scores
        return array_map(function($chunk) use ($queryKeywords) {
            $chunkKeywords = $this->extractKeywords($chunk->content_clean);

            // Calculate keyword overlap score
            $overlap = count(array_intersect_key($queryKeywords, $chunkKeywords));
            $score = $overlap / max(count($queryKeywords), 1);

            $chunk->similarity_score = $score;
            $chunk->metadata = json_decode($chunk->metadata, true);

            return $chunk;
        }, $results);
    }

    /**
     * Search chunks using full-text MySQL search (alternative method)
     */
    public function searchChunksFullText(string $query, int $limit = 5): array {
        global $wpdb;

        $chunksTable = Database::getTableName(Database::TABLE_CHUNKS);

        // Clean query
        $cleanQuery = $this->cleanSearchQuery($query);

        if (empty($cleanQuery)) {
            return $this->searchSimilarChunks($query, $limit);
        }

        // Use LIKE-based search with multiple terms
        $terms = explode(' ', $cleanQuery);
        $whereConditions = [];
        $prepareArgs = [];

        foreach ($terms as $term) {
            if (strlen($term) > 2) {
                $whereConditions[] = "content_clean LIKE %s";
                $prepareArgs[] = '%' . $wpdb->esc_like($term) . '%';
            }
        }

        if (empty($whereConditions)) {
            return [];
        }

        $whereClause = implode(' OR ', $whereConditions);
        $prepareArgs[] = $limit;

        $sql = "
            SELECT *,
                   CHAR_LENGTH(content_clean) as length
            FROM {$chunksTable}
            WHERE {$whereClause}
            ORDER BY word_count DESC
            LIMIT %d
        ";

        $results = $wpdb->get_results($wpdb->prepare($sql, ...$prepareArgs));

        return array_map(function($chunk) {
            $chunk->metadata = json_decode($chunk->metadata, true);
            return $chunk;
        }, $results);
    }

    /**
     * Clean search query
     */
    private function cleanSearchQuery(string $query): string {
        // Remove special characters
        $query = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $query);

        // Remove extra spaces
        $query = preg_replace('/\s+/', ' ', $query);

        // Remove stop words
        $stopWords = $this->getStopWords();
        $words = explode(' ', mb_strtolower($query, 'UTF-8'));
        $words = array_filter($words, function($word) use ($stopWords) {
            return strlen($word) > 2 && !in_array($word, $stopWords);
        });

        return implode(' ', $words);
    }

    /**
     * Get Spanish stop words
     */
    private function getStopWords(): array {
        return [
            'el', 'la', 'de', 'que', 'y', 'a', 'en', 'un', 'ser', 'se', 'no', 'haber',
            'por', 'con', 'su', 'para', 'como', 'estar', 'tener', 'le', 'lo', 'todo',
            'pero', 'más', 'hacer', 'o', 'poder', 'decir', 'este', 'ir', 'otro', 'ese',
            'la', 'si', 'me', 'ya', 'ver', 'porque', 'dar', 'cuando', 'él', 'muy',
            'sin', 'vez', 'mucho', 'saber', 'qué', 'sobre', 'mi', 'alguno', 'mismo',
            'yo', 'también', 'hasta', 'año', 'dos', 'querer', 'entre', 'así', 'primero',
            'desde', 'grande', 'eso', 'ni', 'nos', 'llegar', 'pasar', 'tiempo', 'ella',
            'los', 'las', 'del', 'al', 'una', 'unos', 'unas', 'es', 'son', 'era',
            'fue', 'han', 'has', 'he', 'había', 'hay', 'está', 'están', 'estaba',
            'the', 'and', 'is', 'in', 'it', 'of', 'to', 'for', 'on', 'with', 'as',
            'at', 'by', 'an', 'be', 'this', 'that', 'from', 'or', 'are', 'was',
        ];
    }

    /**
     * Calculate cosine similarity between two vectors (for future use with real embeddings)
     */
    private function cosineSimilarity(array $vec1, array $vec2): float {
        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;

        $allKeys = array_unique(array_merge(array_keys($vec1), array_keys($vec2)));

        foreach ($allKeys as $key) {
            $val1 = $vec1[$key] ?? 0;
            $val2 = $vec2[$key] ?? 0;

            $dotProduct += $val1 * $val2;
            $magnitude1 += $val1 * $val1;
            $magnitude2 += $val2 * $val2;
        }

        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);

        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0;
        }

        return $dotProduct / ($magnitude1 * $magnitude2);
    }
}
