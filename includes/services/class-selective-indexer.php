<?php
/**
 * Selective Indexing Service
 *
 * @package AIChatbotRAG
 */

namespace AIChatbotRAG\Services;

use AIChatbotRAG\Database;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SelectiveIndexer
 * Manages selective indexing of individual posts and pages
 */
class SelectiveIndexer {

    const STATUS_AUTO = 'auto';
    const STATUS_FORCE_INDEX = 'force_index';
    const STATUS_NO_INDEX = 'no_index';

    /**
     * Get indexing status for a post
     */
    public function getPostIndexingStatus(int $postId, string $postType): string {
        global $wpdb;

        $table = Database::getTableName(Database::TABLE_SELECTIVE_INDEXING);
        $status = $wpdb->get_var($wpdb->prepare(
            "SELECT index_status FROM {$table} WHERE post_id = %d AND post_type = %s",
            $postId,
            $postType
        ));

        return $status ?: self::STATUS_AUTO;
    }

    /**
     * Set indexing status for a post
     */
    public function setPostIndexingStatus(int $postId, string $postType, string $status): bool {
        global $wpdb;

        $table = Database::getTableName(Database::TABLE_SELECTIVE_INDEXING);
        
        $result = $wpdb->replace(
            $table,
            [
                'post_id' => $postId,
                'post_type' => $postType,
                'index_status' => $status,
            ],
            ['%d', '%s', '%s']
        );

        return $result !== false;
    }

    /**
     * Check if a post should be indexed based on selective settings
     */
    public function shouldIndexPost(int $postId, string $postType): bool {
        $status = $this->getPostIndexingStatus($postId, $postType);

        switch ($status) {
            case self::STATUS_FORCE_INDEX:
                return true;
            case self::STATUS_NO_INDEX:
                return false;
            case self::STATUS_AUTO:
            default:
                // Use default logic from ContentIndexer
                return true;
        }
    }

    /**
     * Get posts with custom indexing settings
     */
    public function getPostsWithCustomSettings(?string $postType = null, ?string $status = null): array {
        global $wpdb;

        $table = Database::getTableName(Database::TABLE_SELECTIVE_INDEXING);
        $sql = "SELECT * FROM {$table} WHERE 1=1";
        $params = [];

        if ($postType) {
            $sql .= " AND post_type = %s";
            $params[] = $postType;
        }

        if ($status) {
            $sql .= " AND index_status = %s";
            $params[] = $status;
        }

        $sql .= " ORDER BY updated_at DESC";

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        return $wpdb->get_results($sql);
    }

    /**
     * Clear indexing status for a post
     */
    public function clearPostIndexingStatus(int $postId, string $postType): bool {
        global $wpdb;

        $table = Database::getTableName(Database::TABLE_SELECTIVE_INDEXING);
        
        $result = $wpdb->delete(
            $table,
            [
                'post_id' => $postId,
                'post_type' => $postType,
            ],
            ['%d', '%s']
        );

        return $result !== false;
    }

    /**
     * Get available statuses
     */
    public function getAvailableStatuses(): array {
        return [
            self::STATUS_AUTO => __('Auto (default)', 'ai-chatbot-rag'),
            self::STATUS_FORCE_INDEX => __('Force Index', 'ai-chatbot-rag'),
            self::STATUS_NO_INDEX => __('No Index', 'ai-chatbot-rag'),
        ];
    }

    /**
     * Bulk update indexing status for multiple posts
     */
    public function bulkUpdateStatus(array $postIds, string $postType, string $status): int {
        global $wpdb;

        if (empty($postIds)) {
            return 0;
        }

        $table = Database::getTableName(Database::TABLE_SELECTIVE_INDEXING);
        $updated = 0;

        foreach ($postIds as $postId) {
            if ($this->setPostIndexingStatus($postId, $postType, $status)) {
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Get posts that should be indexed based on selective settings
     */
    public function getPostsToIndex(string $postType, array $defaultArgs = []): array {
        $args = wp_parse_args($defaultArgs, [
            'post_type' => $postType,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'modified',
            'order' => 'DESC',
        ]);

        // Get all posts first
        $allPosts = get_posts($args);
        $filteredPosts = [];

        foreach ($allPosts as $post) {
            if ($this->shouldIndexPost($post->ID, $post->post_type)) {
                $filteredPosts[] = $post;
            }
        }

        return $filteredPosts;
    }
}