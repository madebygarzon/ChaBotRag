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
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$embeddingsTable} WHERE chunk_id = %d",
                    $chunk->id
                ));

                if (!$existing) {
                    $embedding = $this->generateEmbedding($chunk->content_clean);

                    $wpdb->insert(
                        $embeddingsTable,
                        [
                            'chunk_id' => $chunk->id,
                            'embedding_model' => $this->embeddingModel,
                            'embedding' => \wp_json_encode($embedding),
                            'dimensions' => count($embedding),
                        ],
                        ['%d', '%s', '%s', '%d']
                    );

                    $stats['processed']++;
                }
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
     * Search chunks using full-text MySQL search (enhanced)
     */
    public function searchChunksFullText(string $query, int $limit = 5, ?string $currentPageUrl = null): array {
        global $wpdb;

        $chunksTable = Database::getTableName(Database::TABLE_CHUNKS);

        // Enhanced search with multiple strategies
        $results = $this->enhancedSearch($query, $limit, $chunksTable, $wpdb, $currentPageUrl);

        // Decode metadata for all results
        return array_map(function($chunk) {
            $chunk->metadata = json_decode($chunk->metadata, true);
            return $chunk;
        }, $results);
    }

    /**
     * Enhanced search with multiple strategies
     */
    private function enhancedSearch(string $query, int $limit, string $chunksTable, $wpdb, ?string $currentPageUrl = null): array {
        // Clean and prepare query
        $cleanQuery = $this->cleanSearchQuery($query);
        
        if (empty($cleanQuery)) {
            return [];
        }

        // Build multiple search conditions
        $terms = explode(' ', $cleanQuery);
        $whereConditions = [];
        $prepareArgs = [];
        $pagePriorityCondition = '';

        // Add page-specific prioritization if current page URL is provided
        $currentPagePath = '';
        if ($currentPageUrl) {
            // Remove query parameters and fragments from URL for comparison
            $currentPagePath = parse_url($currentPageUrl, PHP_URL_PATH);
            if ($currentPagePath) {
                $pagePriorityCondition = "AND (metadata LIKE %s)";
                $prepareArgs[] = '%"' . $wpdb->esc_like($currentPagePath) . '"%';
            }
        }

        // Strategy 1: Exact phrase match (highest priority)
        if (strlen($query) > 3) {
            $whereConditions[] = "content_clean LIKE %s";
            $prepareArgs[] = '%' . $wpdb->esc_like($query) . '%';
        }

        // Strategy 2: Individual terms with synonym matching
        foreach ($terms as $term) {
            if (strlen($term) > 2) {
                $whereConditions[] = "content_clean LIKE %s";
                $prepareArgs[] = '%' . $wpdb->esc_like($term) . '%';
                
                // Add synonym matching for common terms
                $synonyms = $this->getSynonyms($term);
                foreach ($synonyms as $synonym) {
                    $whereConditions[] = "content_clean LIKE %s";
                    $prepareArgs[] = '%' . $wpdb->esc_like($synonym) . '%';
                }
            }
        }

        // Strategy 3: Question-based matching for FAQs
        if (preg_match('/\b(what|how|when|where|why|which|who|is|are|do|does|can|will|should|have)\b/i', $query)) {
            $whereConditions[] = "content_clean LIKE %s";
            $prepareArgs[] = '%Q: ' . $wpdb->esc_like($query) . '%';
        }

        // Strategy 4: Key term matching for courseware-related content
        if (preg_match('/\b(courseware|price|cost|fee|student|payment)\b/i', $query)) {
            $whereConditions[] = "content_clean LIKE %s";
            $prepareArgs[] = '%' . $wpdb->esc_like($query) . '%';
        }

        if (empty($whereConditions)) {
            return [];
        }

        $whereClause = implode(' OR ', $whereConditions);
        $prepareArgs[] = $limit;

        $sql = "
            SELECT *,
                   CHAR_LENGTH(content_clean) as length,
                   CASE 
                     WHEN content_clean LIKE '%Q: %' THEN 3
                     WHEN content_clean LIKE '%What is%' THEN 2
                     WHEN content_clean LIKE '%How is%' THEN 2
                     WHEN content_clean LIKE '%Why is%' THEN 2
                     " . ($currentPagePath ? "WHEN metadata LIKE '%\"" . $wpdb->esc_like($currentPagePath) . "\"%' THEN 5" : "") . "
                     ELSE 1
                   END as priority_score
            FROM {$chunksTable}
            WHERE {$whereClause} {$pagePriorityCondition}
            ORDER BY priority_score DESC, word_count DESC
            LIMIT %d
        ";

        $results = $wpdb->get_results($wpdb->prepare($sql, ...$prepareArgs));

        // Remove duplicates
        $uniqueResults = [];
        $seenIds = [];

        foreach ($results as $result) {
            if (!in_array($result->id, $seenIds)) {
                $uniqueResults[] = $result;
                $seenIds[] = $result->id;
            }
        }

        return array_slice($uniqueResults, 0, $limit);
    }

    /**
     * Search similar chunks (fallback method)
     */
    public function searchSimilarChunks(string $query, int $limit = 5): array {
        global $wpdb;

        $chunksTable = Database::getTableName(Database::TABLE_CHUNKS);
        
        // Simple keyword-based search as fallback
        $cleanQuery = $this->cleanSearchQuery($query);
        if (empty($cleanQuery)) {
            return [];
        }

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
            SELECT *
            FROM {$chunksTable}
            WHERE {$whereClause}
            ORDER BY word_count DESC
            LIMIT %d
        ";

        $results = $wpdb->get_results($wpdb->prepare($sql, ...$prepareArgs));

        // Decode metadata for all results
        return array_map(function($chunk) {
            $chunk->metadata = json_decode($chunk->metadata, true);
            return $chunk;
        }, $results);
    }

    /**
     * Clean search query
     */
    private function cleanSearchQuery(string $query): string {
        // Preserve question marks and important punctuation for FAQ matching
        $query = preg_replace('/[^\p{L}\p{N}\s\?]/u', ' ', $query);
        
        // Remove extra spaces
        $query = preg_replace('/\s+/', ' ', $query);
        
        // Remove stop words but preserve important question words
        $stopWords = $this->getStopWords();
        $words = explode(' ', mb_strtolower($query, 'UTF-8'));
        $words = array_filter($words, function($word) use ($stopWords) {
            return strlen($word) > 2 && !in_array($word, $stopWords);
        });
        
        return implode(' ', $words);
    }

    /**
     * Get synonyms for search terms
     */
    private function getSynonyms(string $term): array {
        $synonymMap = [
            'price' => ['cost', 'fee', 'charge', 'payment'],
            'cost' => ['price', 'fee', 'charge', 'payment'],
            'fee' => ['price', 'cost', 'charge', 'payment'],
            'courseware' => ['course', 'software', 'platform', 'tool'],
            'student' => ['user', 'learner', 'participant'],
            'use' => ['utilize', 'access', 'work with'],
            'have' => ['is there', 'are there', 'do', 'does'],
        ];

        $term = strtolower($term);
        return $synonymMap[$term] ?? [];
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
     * Generate embedding for text (MVP: simple keyword extraction)
     */
    private function generateEmbedding(string $text): array {
        // For MVP, just return keywords as "embedding"
        $words = str_word_count($text);
        $keywords = $this->extractKeywords($text);
        
        return array_merge($keywords, [$words]);
    }

    /**
     * Extract keywords from text
     */
    private function extractKeywords(string $text): array {
        // Simple keyword extraction for MVP
        $words = preg_split('/\s+/', strtolower($text));
        $stopWords = $this->getStopWords();
        
        $keywords = [];
        foreach ($words as $word) {
            if (strlen($word) > 3 && !in_array($word, $stopWords)) {
                $keywords[] = $word;
            }
        }
        
        return array_unique($keywords);
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