<?php
/**
 * Admin Interface
 *
 * @package AIChatbotRAG
 */

namespace AIChatbotRAG\Admin;

use AIChatbotRAG\Services\ContentIndexer;
use AIChatbotRAG\Services\EmbeddingsService;

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
            wp_send_json_error(['message' => $e->getMessage()]);
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
}
