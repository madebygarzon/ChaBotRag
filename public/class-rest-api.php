<?php
/**
 * REST API Endpoints
 *
 * @package AIChatbotRAG
 */

namespace AIChatbotRAG\PublicFacing;

use AIChatbotRAG\Services\RAGEngine;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class RestAPI
 */
class RestAPI {

    private const NAMESPACE = 'ai-chatbot-rag/v1';
    private RAGEngine $ragEngine;

    public function __construct() {
        $this->ragEngine = new RAGEngine();
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    /**
     * Register REST API routes
     */
    public function registerRoutes(): void {
        // Chat endpoint
        register_rest_route(self::NAMESPACE, '/chat', [
            'methods' => 'POST',
            'callback' => [$this, 'handleChat'],
            'permission_callback' => [$this, 'checkChatPermission'],
            'args' => [
                'message' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                    'validate_callback' => function($value) {
                        return !empty(trim($value)) && strlen($value) <= 5000;
                    },
                ],
                'session_id' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function($value) {
                        return !empty($value) && strlen($value) <= 64;
                    },
                ],
                'current_page_url' => [
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'esc_url_raw',
                ],
            ],
        ]);

        // Health check endpoint
        register_rest_route(self::NAMESPACE, '/health', [
            'methods' => 'GET',
            'callback' => [$this, 'handleHealthCheck'],
            'permission_callback' => '__return_true',
        ]);

        // Stats endpoint (admin only)
        register_rest_route(self::NAMESPACE, '/stats', [
            'methods' => 'GET',
            'callback' => [$this, 'handleStats'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);
    }

    /**
     * Check if user has permission to chat
     */
    public function checkChatPermission(): bool {
        // Check if chatbot is enabled
        if (!get_option('ai_chatbot_rag_chatbot_enabled', true)) {
            return false;
        }

        // Apply rate limiting
        if (!$this->checkRateLimit()) {
            return false;
        }

        return true;
    }

    /**
     * Handle chat request
     */
    public function handleChat(\WP_REST_Request $request): \WP_REST_Response {
        error_log('AI Chatbot RAG: Chat request received');

        $message = $request->get_param('message');
        $sessionId = $request->get_param('session_id');
        $currentPageUrl = $request->get_param('current_page_url');
        $userId = get_current_user_id() ?: null;

        error_log('AI Chatbot RAG: Message: ' . substr($message, 0, 100));
        error_log('AI Chatbot RAG: Session ID: ' . $sessionId);

        // Validate message
        if (empty(trim($message))) {
            error_log('AI Chatbot RAG: Empty message error');
            return new \WP_REST_Response([
                'success' => false,
                'error' => __('Message cannot be empty', 'ai-chatbot-rag'),
            ], 400);
        }

        // Process chat
        try {
            error_log('AI Chatbot RAG: Starting RAG engine chat');
            $result = $this->ragEngine->chat($message, $sessionId, $userId, $currentPageUrl);
            error_log('AI Chatbot RAG: RAG engine result: ' . wp_json_encode($result));

            if (!$result['success']) {
                return new \WP_REST_Response([
                    'success' => false,
                    'error' => $result['error'] ?? __('Unknown error', 'ai-chatbot-rag'),
                    'response' => $result['response'],
                ], 500);
            }

            return new \WP_REST_Response([
                'success' => true,
                'response' => $result['response'],
                'sources' => $result['sources'] ?? [],
                'metadata' => $result['metadata'] ?? [],
            ], 200);
        } catch (\Exception $e) {
            error_log('AI Chatbot RAG: Chat API Error: ' . $e->getMessage());
            error_log('AI Chatbot RAG: Stack trace: ' . $e->getTraceAsString());

            return new \WP_REST_Response([
                'success' => false,
                'error' => __('An error occurred while processing your request', 'ai-chatbot-rag'),
                'response' => __('I apologize, but an error occurred. Please try again or contact our team at https://partnerinpublishing.com/#brxe-8292d9', 'ai-chatbot-rag'),
                'debug' => WP_DEBUG ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Handle health check
     */
    public function handleHealthCheck(): \WP_REST_Response {
        $apiKey = get_option('ai_chatbot_rag_deepseek_api_key', '');

        return new \WP_REST_Response([
            'status' => 'ok',
            'version' => AI_CHATBOT_RAG_VERSION,
            'api_configured' => !empty($apiKey),
            'chatbot_enabled' => (bool) get_option('ai_chatbot_rag_chatbot_enabled', true),
        ], 200);
    }

    /**
     * Handle stats request
     */
    public function handleStats(): \WP_REST_Response {
        global $wpdb;

        $chunksTable = \AIChatbotRAG\Database::getTableName(\AIChatbotRAG\Database::TABLE_CHUNKS);
        $conversationsTable = \AIChatbotRAG\Database::getTableName(\AIChatbotRAG\Database::TABLE_CONVERSATIONS);

        $stats = [
            'total_chunks' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$chunksTable}"),
            'total_conversations' => (int) $wpdb->get_var("SELECT COUNT(DISTINCT session_id) FROM {$conversationsTable}"),
            'total_messages' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$conversationsTable}"),
        ];

        return new \WP_REST_Response($stats, 200);
    }

    /**
     * Simple rate limiting based on IP and session
     */
    private function checkRateLimit(): bool {
        $ip = $this->getClientIP();
        $transientKey = 'ai_chatbot_rate_limit_' . md5($ip);

        $requestCount = get_transient($transientKey);

        if ($requestCount === false) {
            // First request in this minute
            set_transient($transientKey, 1, MINUTE_IN_SECONDS);
            return true;
        }

        // Allow up to 10 requests per minute
        if ($requestCount >= 10) {
            return false;
        }

        set_transient($transientKey, $requestCount + 1, MINUTE_IN_SECONDS);
        return true;
    }

    /**
     * Get client IP address
     */
    private function getClientIP(): string {
        $ip = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }

        return sanitize_text_field($ip);
    }
}
