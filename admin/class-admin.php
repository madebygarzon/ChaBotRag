<?php
/**
 * Admin Interface
 *
 * @package AIChatbotRAG
 */

namespace AIChatbotRAG\Admin;

use AIChatbotRAG\Services\ContentIndexer;
use AIChatbotRAG\Services\EmbeddingsService;
use AIChatbotRAG\Services\SelectiveIndexer;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Admin
 */
class Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('wp_ajax_ai_chatbot_index_content', [$this, 'ajaxIndexContent']);
        add_action('wp_ajax_ai_chatbot_clear_data', [$this, 'ajaxClearData']);
        add_action('wp_ajax_ai_chatbot_get_stats', [$this, 'ajaxGetStats']);
        add_action('wp_ajax_ai_chatbot_debug_indexing', [$this, 'ajaxDebugIndexing']);
        add_action('wp_ajax_ai_chatbot_get_posts_for_indexing', [$this, 'ajaxGetPostsForIndexing']);
        add_action('wp_ajax_ai_chatbot_get_pages_for_indexing', [$this, 'ajaxGetPagesForIndexing']);
        add_action('wp_ajax_ai_chatbot_get_custom_indexing_settings', [$this, 'ajaxGetCustomIndexingSettings']);
        add_action('wp_ajax_ai_chatbot_update_indexing_status', [$this, 'ajaxUpdateIndexingStatus']);
        add_action('wp_ajax_ai_chatbot_clear_indexing_status', [$this, 'ajaxClearIndexingStatus']);
        add_action('wp_ajax_ai_chatbot_bulk_update_indexing_status', [$this, 'ajaxBulkUpdateIndexingStatus']);
    }

    /**
     * Add admin menu
     */
    public function addAdminMenu(): void {

        add_menu_page(
            __('AI Chatbot RAG', 'ai-chatbot-rag'),
            __('AI Chatbot', 'ai-chatbot-rag'),
            'manage_options',
            'ai-chatbot-rag',
            [$this, 'renderDashboardPage'],
            'dashicons-format-chat',
            30
        );

        add_submenu_page(
            'ai-chatbot-rag',
            __('Dashboard', 'ai-chatbot-rag'),
            __('Dashboard', 'ai-chatbot-rag'),
            'manage_options',
            'ai-chatbot-rag',
            [$this, 'renderDashboardPage']
        );

        add_submenu_page(
            'ai-chatbot-rag',
            __('Settings', 'ai-chatbot-rag'),
            __('Settings', 'ai-chatbot-rag'),
            'manage_options',
            'ai-chatbot-rag-settings',
            [$this, 'renderSettingsPage']
        );

        add_submenu_page(
            'ai-chatbot-rag',
            __('Indexing', 'ai-chatbot-rag'),
            __('Indexing', 'ai-chatbot-rag'),
            'manage_options',
            'ai-chatbot-rag-indexing',
            [$this, 'renderIndexingPage']
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueueAssets(string $hook): void {
        if (strpos($hook, 'ai-chatbot-rag') === false) {
            return;
        }

        // Enqueue WordPress color picker
        wp_enqueue_style('wp-color-picker');

        // Enqueue WordPress media uploader
        wp_enqueue_media();

        wp_enqueue_style(
            'ai-chatbot-rag-admin',
            AI_CHATBOT_RAG_PLUGIN_URL . 'assets/css/admin.css',
            [],
            AI_CHATBOT_RAG_VERSION
        );

        wp_enqueue_script(
            'ai-chatbot-rag-admin',
            AI_CHATBOT_RAG_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'wp-color-picker'],
            AI_CHATBOT_RAG_VERSION,
            true
        );

        wp_localize_script('ai-chatbot-rag-admin', 'aiChatbotRAG', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_chatbot_rag_admin'),
            'i18n' => [
                'indexing' => __('Indexing content...', 'ai-chatbot-rag'),
                'indexingComplete' => __('Indexing complete!', 'ai-chatbot-rag'),
                'indexingError' => __('Error during indexing', 'ai-chatbot-rag'),
                'clearingData' => __('Clearing data...', 'ai-chatbot-rag'),
                'dataCleared' => __('All data cleared!', 'ai-chatbot-rag'),
                'confirmClear' => __('Are you sure you want to clear all indexed data? This cannot be undone.', 'ai-chatbot-rag'),
            ],
        ]);
    }

    /**
     * Render dashboard page
     */
    public function renderDashboardPage(): void {
        $indexer = new ContentIndexer();
        $stats = $indexer->getStats();

        include AI_CHATBOT_RAG_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    /**
     * Render settings page
     */
    public function renderSettingsPage(): void {
        include AI_CHATBOT_RAG_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * Render indexing page
     */
    public function renderIndexingPage(): void {
        $indexer = new ContentIndexer();
        $stats = $indexer->getStats();

        include AI_CHATBOT_RAG_PLUGIN_DIR . 'admin/views/indexing.php';
    }

    /**
     * AJAX: Index content
     */
    public function ajaxIndexContent(): void {
        check_ajax_referer('ai_chatbot_rag_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ai-chatbot-rag')]);
        }

        try {
            // Enable error reporting for debugging
            error_reporting(E_ALL);
            ini_set('display_errors', 0); // Don't display, just log
            
            $indexer = new ContentIndexer();
            $stats = $indexer->indexAllContent();

            // Generate embeddings
            $embeddingsService = new EmbeddingsService();
            $embeddingsStats = $embeddingsService->generateAllEmbeddings();

            wp_send_json_success([
                'message' => __('Content indexed successfully!', 'ai-chatbot-rag'),
                'stats' => array_merge($stats, $embeddingsStats),
            ]);
        } catch (\Exception $e) {
            // Log detailed error
            error_log('AI Chatbot RAG Indexing Error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            wp_send_json_error([
                'message' => 'Indexing error: ' . $e->getMessage(),
                'debug_info' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]
            ]);
        } catch (\Error $e) {
            // Catch PHP errors too
            error_log('AI Chatbot RAG PHP Error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            wp_send_json_error([
                'message' => 'PHP error: ' . $e->getMessage(),
                'debug_info' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]
            ]);
        }
    }

    /**
     * AJAX: Debug indexing
     */
    public function ajaxDebugIndexing(): void {
        check_ajax_referer('ai_chatbot_rag_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ai-chatbot-rag')]);
        }

        try {
            // Test a single post
            $posts = get_posts([
                'post_type' => 'page',
                'post_status' => 'publish',
                'posts_per_page' => 1,
                'orderby' => 'modified',
                'order' => 'DESC'
            ]);

            if (empty($posts)) {
                wp_send_json_error(['message' => 'No posts found to test']);
            }

            $testPost = $posts[0];
            $indexer = new ContentIndexer();
            
            // Test content extraction
            $content = $indexer->extractPostContent($testPost);
            
            // Test Bricks extraction
            $bricksExtractor = new \AIChatbotRAG\Services\BricksContentExtractor();
            $bricksContent = $bricksExtractor->extractContent($testPost->ID);
            
            wp_send_json_success([
                'message' => 'Debug test completed',
                'debug_info' => [
                    'post' => [
                        'ID' => $testPost->ID,
                        'title' => $testPost->post_title,
                        'type' => $testPost->post_type
                    ],
                    'extracted_content' => substr($content, 0, 1000) . '...',
                    'bricks_content' => substr($bricksContent, 0, 1000) . '...',
                    'bricks_data_exists' => !empty(get_post_meta($testPost->ID, '_bricks_data', true)),
                    'post_content_length' => strlen($testPost->post_content)
                ]
            ]);
            
        } catch (\Exception $e) {
            error_log('AI Chatbot RAG Debug Error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => 'Debug error: ' . $e->getMessage(),
                'debug_info' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]
            ]);
        }
    }

    /**
     * AJAX: Clear all data
     */
    public function ajaxClearData(): void {
        check_ajax_referer('ai_chatbot_rag_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ai-chatbot-rag')]);
        }

        try {
            global $wpdb;
            \AIChatbotRAG\Database::clearAllData();

            wp_send_json_success([
                'message' => __('All data cleared successfully!', 'ai-chatbot-rag'),
            ]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * AJAX: Get statistics
     */
    public function ajaxGetStats(): void {
        check_ajax_referer('ai_chatbot_rag_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ai-chatbot-rag')]);
        }

        $indexer = new ContentIndexer();
        $stats = $indexer->getStats();

        wp_send_json_success(['stats' => $stats]);
    }

    /**
     * AJAX: Get posts for selective indexing
     */
    public function ajaxGetPostsForIndexing(): void {
        check_ajax_referer('ai_chatbot_rag_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ai-chatbot-rag')]);
        }

        $search = sanitize_text_field($_POST['search'] ?? '');
        $perPage = (int) ($_POST['per_page'] ?? 20);

        $args = [
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $perPage,
            'orderby' => 'modified',
            'order' => 'DESC',
        ];

        if (!empty($search)) {
            $args['s'] = $search;
        }

        $posts = get_posts($args);
        $selectiveIndexer = new SelectiveIndexer();
        
        $statuses = [];
        foreach ($posts as $post) {
            $statuses[$post->ID] = $selectiveIndexer->getPostIndexingStatus($post->ID, $post->post_type);
        }

        wp_send_json_success([
            'posts' => array_map(function($post) {
                return [
                    'ID' => $post->ID,
                    'post_title' => $post->post_title,
                    'post_type' => $post->post_type,
                    'post_modified' => $post->post_modified,
                ];
            }, $posts),
            'statuses' => $statuses,
        ]);
    }

    /**
     * AJAX: Get pages for selective indexing
     */
    public function ajaxGetPagesForIndexing(): void {
        check_ajax_referer('ai_chatbot_rag_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ai-chatbot-rag')]);
        }

        $search = sanitize_text_field($_POST['search'] ?? '');
        $perPage = (int) ($_POST['per_page'] ?? 20);

        $args = [
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => $perPage,
            'orderby' => 'modified',
            'order' => 'DESC',
        ];

        if (!empty($search)) {
            $args['s'] = $search;
        }

        $pages = get_posts($args);
        $selectiveIndexer = new SelectiveIndexer();
        
        $statuses = [];
        foreach ($pages as $page) {
            $statuses[$page->ID] = $selectiveIndexer->getPostIndexingStatus($page->ID, $page->post_type);
        }

        wp_send_json_success([
            'pages' => array_map(function($page) {
                return [
                    'ID' => $page->ID,
                    'post_title' => $page->post_title,
                    'post_type' => $page->post_type,
                    'post_modified' => $page->post_modified,
                ];
            }, $pages),
            'statuses' => $statuses,
        ]);
    }

    /**
     * AJAX: Get custom indexing settings
     */
    public function ajaxGetCustomIndexingSettings(): void {
        check_ajax_referer('ai_chatbot_rag_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ai-chatbot-rag')]);
        }

        $selectiveIndexer = new SelectiveIndexer();
        $settings = $selectiveIndexer->getPostsWithCustomSettings();

        // Enrich with post titles
        foreach ($settings as &$setting) {
            $post = get_post($setting->post_id);
            if ($post) {
                $setting->post_title = $post->post_title;
            }
        }

        wp_send_json_success(['settings' => $settings]);
    }

    /**
     * AJAX: Update indexing status for a post
     */
    public function ajaxUpdateIndexingStatus(): void {
        check_ajax_referer('ai_chatbot_rag_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ai-chatbot-rag')]);
        }

        $postId = (int) $_POST['post_id'];
        $postType = sanitize_text_field($_POST['post_type']);
        $status = sanitize_text_field($_POST['status']);

        if (!in_array($status, ['auto', 'force_index', 'no_index'])) {
            wp_send_json_error(['message' => __('Invalid status', 'ai-chatbot-rag')]);
        }

        $selectiveIndexer = new SelectiveIndexer();
        
        if ($status === 'auto') {
            // Clear custom setting to revert to auto
            $result = $selectiveIndexer->clearPostIndexingStatus($postId, $postType);
        } else {
            $result = $selectiveIndexer->setPostIndexingStatus($postId, $postType, $status);
        }

        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error(['message' => __('Failed to update status', 'ai-chatbot-rag')]);
        }
    }

    /**
     * AJAX: Clear indexing status for a post
     */
    public function ajaxClearIndexingStatus(): void {
        check_ajax_referer('ai_chatbot_rag_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ai-chatbot-rag')]);
        }

        $postId = (int) $_POST['post_id'];
        $postType = sanitize_text_field($_POST['post_type']);

        $selectiveIndexer = new SelectiveIndexer();
        $result = $selectiveIndexer->clearPostIndexingStatus($postId, $postType);

        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error(['message' => __('Failed to clear status', 'ai-chatbot-rag')]);
        }
    }

    /**
     * AJAX: Bulk update indexing status
     */
    public function ajaxBulkUpdateIndexingStatus(): void {
        check_ajax_referer('ai_chatbot_rag_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ai-chatbot-rag')]);
        }

        $updates = $_POST['updates'] ?? [];
        $status = sanitize_text_field($_POST['status']);

        if (!in_array($status, ['auto', 'force_index', 'no_index'])) {
            wp_send_json_error(['message' => __('Invalid status', 'ai-chatbot-rag')]);
        }

        if (empty($updates) || !is_array($updates)) {
            wp_send_json_error(['message' => __('No items selected', 'ai-chatbot-rag')]);
        }

        $selectiveIndexer = new SelectiveIndexer();
        $updated = 0;

        foreach ($updates as $update) {
            $postId = (int) ($update['id'] ?? 0);
            $postType = sanitize_text_field($update['type'] ?? '');

            if ($postId > 0 && !empty($postType)) {
                if ($status === 'auto') {
                    if ($selectiveIndexer->clearPostIndexingStatus($postId, $postType)) {
                        $updated++;
                    }
                } else {
                    if ($selectiveIndexer->setPostIndexingStatus($postId, $postType, $status)) {
                        $updated++;
                    }
                }
            }
        }

        wp_send_json_success(['updated' => $updated]);
    }
}
