<?php
/**
 * Content Indexer Service
 *
 * @package AIChatbotRAG
 */

namespace AIChatbotRAG\Services;

use AIChatbotRAG\Database;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ContentIndexer
 * Indexes WordPress content into searchable chunks
 */
class ContentIndexer {

    private int $chunkSize;
    private int $chunkOverlap;
    private array $enabledPostTypes;
    private array $excludeCategories;
    private SelectiveIndexer $selectiveIndexer;
    private BricksContentExtractor $bricksExtractor;

    public function __construct() {
        $this->chunkSize = (int) get_option('ai_chatbot_rag_chunk_size', 500);
        $this->chunkOverlap = (int) get_option('ai_chatbot_rag_chunk_overlap', 50);
        $this->enabledPostTypes = get_option('ai_chatbot_rag_enabled_post_types', ['post', 'page']);
        $this->excludeCategories = get_option('ai_chatbot_rag_exclude_categories', []);
        $this->selectiveIndexer = new SelectiveIndexer();
        $this->bricksExtractor = new BricksContentExtractor();
    }

    /**
     * Index all site content
     */
    public function indexAllContent(): array {
        $stats = [
            'total_posts' => 0,
            'indexed_posts' => 0,
            'total_chunks' => 0,
            'errors' => [],
        ];

        foreach ($this->enabledPostTypes as $postType) {
            $posts = $this->getPostsToIndex($postType);
            $stats['total_posts'] += count($posts);

            foreach ($posts as $post) {
                try {
                    $chunks = $this->indexPost($post->ID);
                    $stats['indexed_posts']++;
                    $stats['total_chunks'] += count($chunks);
                } catch (\Exception $e) {
                    $stats['errors'][] = sprintf(
                        'Error indexing post %d: %s',
                        $post->ID,
                        $e->getMessage()
                    );
                }
            }
        }

        // Index WooCommerce products if WooCommerce is active
        if (class_exists('WooCommerce') && in_array('product', $this->enabledPostTypes)) {
            $products = $this->getPostsToIndex('product');
            $stats['total_posts'] += count($products);

            foreach ($products as $product) {
                try {
                    $chunks = $this->indexProduct($product->ID);
                    $stats['indexed_posts']++;
                    $stats['total_chunks'] += count($chunks);
                } catch (\Exception $e) {
                    $stats['errors'][] = sprintf(
                        'Error indexing product %d: %s',
                        $product->ID,
                        $e->getMessage()
                    );
                }
            }
        }

        return $stats;
    }

    /**
     * Index a single post
     */
    public function indexPost(int $postId): array {
        global $wpdb;

        $post = get_post($postId);
        if (!$post || !$this->shouldIndexPost($post)) {
            return [];
        }

        // Delete existing chunks for this post
        $this->deletePostChunks($postId);

        // Extract and clean content
        $content = $this->extractPostContent($post);
        $cleanContent = $this->cleanContent($content);

        // Create chunks
        $chunks = $this->createChunks($cleanContent);

        // Save chunks to database
        $chunksTable = Database::getTableName(Database::TABLE_CHUNKS);
        $savedChunks = [];

        foreach ($chunks as $index => $chunk) {
            $metadata = $this->extractMetadata($post);

            $wpdb->insert(
                $chunksTable,
                [
                    'post_id' => $postId,
                    'post_type' => $post->post_type,
                    'chunk_index' => $index,
                    'content_hash' => md5($chunk),
                    'content' => $chunk,
                    'content_clean' => $chunk,
                    'word_count' => str_word_count($chunk),
                    'metadata' => wp_json_encode($metadata),
                ],
                ['%d', '%s', '%d', '%s', '%s', '%s', '%d', '%s']
            );

            if ($wpdb->insert_id) {
                $savedChunks[] = $wpdb->insert_id;
            }
        }

        return $savedChunks;
    }

    /**
     * Index a WooCommerce product
     */
    private function indexProduct(int $productId): array {
        if (!function_exists('wc_get_product')) {
            return [];
        }

        $product = wc_get_product($productId);
        if (!$product) {
            return [];
        }

        $post = get_post($productId);
        $this->deletePostChunks($productId);

        // Build product content
        $content = sprintf(
            "%s\n\n%s\n\nPrecio: %s\n\n%s",
            $product->get_name(),
            $product->get_short_description(),
            $product->get_price_html(),
            $product->get_description()
        );

        // Add product attributes
        $attributes = $product->get_attributes();
        if (!empty($attributes)) {
            $content .= "\n\nCaracterísticas:\n";
            foreach ($attributes as $attribute) {
                if (is_a($attribute, 'WC_Product_Attribute')) {
                    $content .= sprintf("- %s: %s\n", wc_attribute_label($attribute->get_name()), $product->get_attribute($attribute->get_name()));
                }
            }
        }

        $cleanContent = $this->cleanContent($content);
        $chunks = $this->createChunks($cleanContent);

        global $wpdb;
        $chunksTable = Database::getTableName(Database::TABLE_CHUNKS);
        $savedChunks = [];

        foreach ($chunks as $index => $chunk) {
            $metadata = [
                'title' => $product->get_name(),
                'type' => 'product',
                'url' => get_permalink($productId),
                'sku' => $product->get_sku(),
                'price' => $product->get_price(),
            ];

            $wpdb->insert(
                $chunksTable,
                [
                    'post_id' => $productId,
                    'post_type' => 'product',
                    'chunk_index' => $index,
                    'content_hash' => md5($chunk),
                    'content' => $chunk,
                    'content_clean' => $chunk,
                    'word_count' => str_word_count($chunk),
                    'metadata' => wp_json_encode($metadata),
                ],
                ['%d', '%s', '%d', '%s', '%s', '%s', '%d', '%s']
            );

            if ($wpdb->insert_id) {
                $savedChunks[] = $wpdb->insert_id;
            }
        }

        return $savedChunks;
    }

    /**
     * Get posts to index
     */
    private function getPostsToIndex(string $postType): array {
        $args = [
            'post_type' => $postType,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'modified',
            'order' => 'DESC',
        ];

        if (!empty($this->excludeCategories) && $postType === 'post') {
            $args['category__not_in'] = $this->excludeCategories;
        }

        // Use selective indexer to get filtered posts
        return $this->selectiveIndexer->getPostsToIndex($postType, $args);
    }

    /**
     * Check if post should be indexed
     */
    private function shouldIndexPost(\WP_Post $post): bool {
        // Check post status
        if ($post->post_status !== 'publish') {
            return false;
        }

        // Check if post type is enabled
        if (!in_array($post->post_type, $this->enabledPostTypes)) {
            return false;
        }

        // Check selective indexing settings first
        if (!$this->selectiveIndexer->shouldIndexPost($post->ID, $post->post_type)) {
            return false;
        }

        // Check excluded categories
        if (!empty($this->excludeCategories) && $post->post_type === 'post') {
            $categories = wp_get_post_categories($post->ID);
            if (array_intersect($categories, $this->excludeCategories)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Extract post content including ACF fields and Bricks elements
     */
    private function extractPostContent(\WP_Post $post): string {
        $content = sprintf(
            "%s\n\n%s\n\n%s",
            $post->post_title,
            $post->post_excerpt,
            $post->post_content
        );

        // Extract content from Bricks elements using specialized extractor
        $bricksContent = $this->bricksExtractor->extractContent($post->ID);
        if ($bricksContent) {
            $content .= "\n\n" . $bricksContent;
        }

        // Add ACF fields if available
        if (function_exists('get_fields')) {
            $fields = get_fields($post->ID);
            if (!empty($fields) && is_array($fields)) {
                $content .= "\n\n";
                foreach ($fields as $key => $value) {
                    if (is_string($value) || is_numeric($value)) {
                        $content .= sprintf("%s: %s\n", $key, $value);
                    }
                }
            }
        }

        return $content;
    }

    /**
     * Clean content from HTML, shortcodes, and scripts
     */
    private function cleanContent(string $content): string {
        try {
            // Remove shortcodes
            $content = strip_shortcodes($content);

            // Handle Q: and A: format from Bricks extraction
            if (preg_match('/Q:.*?\nA:.*?\n/s', $content)) {
                // This is already in Q/A format, just clean it up
                $content = preg_replace('/\s+/', ' ', $content);
                $content = trim($content);
                return $content;
            }

            // Remove HTML tags
            $content = wp_strip_all_tags($content);

            // Remove script and style tags content
            $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content);
            $content = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $content);

            // Remove WordPress blocks HTML comments
            $content = preg_replace('/<!-- wp:.*? -->/', '', $content);
            $content = preg_replace('/<!-- \/wp:.*? -->/', '', $content);

            // Remove HTML entities and special characters
            $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');

            // Remove multiple spaces and newlines, but preserve Q/A structure
            $content = preg_replace('/[ \t]+/', ' ', $content);
            $content = preg_replace('/\n\s*\n/', "\n", $content);
            $content = preg_replace('/\s*\n\s*/', "\n", $content);

            // Trim
            $content = trim($content);

            // Limit content length to avoid issues
            if (strlen($content) > 100000) { // 100KB limit
                $content = substr($content, 0, 100000) . '...';
            }

            return $content;
        } catch (\Exception $e) {
            error_log('Content cleaning error: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Create text chunks with overlap
     */
    private function createChunks(string $text): array {
        $words = preg_split('/\s+/', $text);
        $chunks = [];
        $currentChunk = [];
        $wordCount = 0;

        foreach ($words as $word) {
            $currentChunk[] = $word;
            $wordCount++;

            if ($wordCount >= $this->chunkSize) {
                $chunks[] = implode(' ', $currentChunk);

                // Keep overlap words for next chunk
                $currentChunk = array_slice($currentChunk, -$this->chunkOverlap);
                $wordCount = count($currentChunk);
            }
        }

        // Add remaining words as last chunk
        if (!empty($currentChunk)) {
            $chunks[] = implode(' ', $currentChunk);
        }

        return array_filter($chunks); // Remove empty chunks
    }

    /**
     * Extract metadata from post
     */
    private function extractMetadata(\WP_Post $post): array {
        $metadata = [
            'title' => $post->post_title,
            'type' => $post->post_type,
            'url' => get_permalink($post->ID),
            'author' => get_the_author_meta('display_name', $post->post_author),
            'date' => $post->post_date,
        ];

        // Add categories/tags for posts
        if ($post->post_type === 'post') {
            $categories = wp_get_post_categories($post->ID, ['fields' => 'names']);
            $tags = wp_get_post_tags($post->ID, ['fields' => 'names']);

            $metadata['categories'] = $categories;
            $metadata['tags'] = $tags;
        }

        return $metadata;
    }

    /**
     * Delete chunks for a post
     */
    private function deletePostChunks(int $postId): void {
        global $wpdb;

        $chunksTable = Database::getTableName(Database::TABLE_CHUNKS);
        $embeddingsTable = Database::getTableName(Database::TABLE_EMBEDDINGS);

        // Get chunk IDs
        $chunkIds = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM {$chunksTable} WHERE post_id = %d",
            $postId
        ));

        if (!empty($chunkIds)) {
            // Delete embeddings
            $placeholders = implode(',', array_fill(0, count($chunkIds), '%d'));
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$embeddingsTable} WHERE chunk_id IN ({$placeholders})",
                ...$chunkIds
            ));
        }

        // Delete chunks
        $wpdb->delete($chunksTable, ['post_id' => $postId], ['%d']);
    }

    /**
     * Get indexing statistics
     */
    public function getStats(): array {
        global $wpdb;

        $chunksTable = Database::getTableName(Database::TABLE_CHUNKS);
        $embeddingsTable = Database::getTableName(Database::TABLE_EMBEDDINGS);

        return [
            'total_chunks' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$chunksTable}"),
            'total_embeddings' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$embeddingsTable}"),
            'indexed_posts' => (int) $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$chunksTable}"),
            'post_types' => $wpdb->get_results("SELECT post_type, COUNT(*) as count FROM {$chunksTable} GROUP BY post_type"),
        ];
    }
}