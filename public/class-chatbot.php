<?php
/**
 * Chatbot Widget and Shortcode
 *
 * @package AIChatbotRAG
 */

namespace AIChatbotRAG\PublicFacing;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Chatbot
 */
class Chatbot {

    public function __construct() {
        add_shortcode('ai_chatbot', [$this, 'renderShortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('wp_footer', [$this, 'renderFloatingWidget']);
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueueAssets(): void {
        if (!$this->shouldLoadChatbot()) {
            return;
        }

        wp_enqueue_style(
            'ai-chatbot-rag-public',
            AI_CHATBOT_RAG_PLUGIN_URL . 'assets/css/chatbot.css',
            [],
            AI_CHATBOT_RAG_VERSION
        );

        wp_enqueue_script(
            'ai-chatbot-rag-public',
            AI_CHATBOT_RAG_PLUGIN_URL . 'assets/js/chatbot.js',
            [],
            AI_CHATBOT_RAG_VERSION,
            true
        );

        wp_localize_script('ai-chatbot-rag-public', 'aiChatbotConfig', [
            'apiUrl' => rest_url('ai-chatbot-rag/v1/chat'),
            'nonce' => wp_create_nonce('wp_rest'),
            'sessionId' => $this->getOrCreateSessionId(),
            'title' => get_option('ai_chatbot_rag_chatbot_title', __('¿Cómo podemos ayudarte?', 'ai-chatbot-rag')),
            'placeholder' => get_option('ai_chatbot_rag_chatbot_placeholder', __('Escribe tu pregunta...', 'ai-chatbot-rag')),
            'buttonPosition' => get_option('ai_chatbot_rag_button_position', 'bottom-right'),
            'buttonIcon' => get_option('ai_chatbot_rag_button_icon', 'chat'),
            'primaryColor' => get_option('ai_chatbot_rag_primary_color', '#CF142B'),
            'botAvatarSize' => get_option('ai_chatbot_rag_bot_avatar_size', 28),
            'i18n' => [
                'send' => __('Send', 'ai-chatbot-rag'),
                'thinking' => __('Thinking...', 'ai-chatbot-rag'),
                'error' => __('An error occurred. Please try again.', 'ai-chatbot-rag'),
                'sources' => __('Sources:', 'ai-chatbot-rag'),
                'placeholder' => get_option('ai_chatbot_rag_chatbot_placeholder', __('Escribe tu pregunta...', 'ai-chatbot-rag')),
            ],
        ]);
    }

    /**
     * Render shortcode
     */
    public function renderShortcode(array $atts = []): string {
        $atts = shortcode_atts([
            'title' => get_option('ai_chatbot_rag_chatbot_title', __('¿Cómo podemos ayudarte?', 'ai-chatbot-rag')),
            'height' => '500px',
        ], $atts);

        ob_start();
        include AI_CHATBOT_RAG_PLUGIN_DIR . 'public/views/chatbot-shortcode.php';
        return ob_get_clean();
    }

    /**
     * Render floating widget
     */
    public function renderFloatingWidget(): void {
        if (!$this->shouldLoadChatbot()) {
            return;
        }

        $position = get_option('ai_chatbot_rag_button_position', 'bottom-right');
        $icon = get_option('ai_chatbot_rag_button_icon', 'chat');
        $title = get_option('ai_chatbot_rag_chatbot_title', __('How can we help?', 'ai-chatbot-rag'));
        $placeholder = get_option('ai_chatbot_rag_chatbot_placeholder', __('Type your question...', 'ai-chatbot-rag'));

        include AI_CHATBOT_RAG_PLUGIN_DIR . 'public/views/floating-widget.php';
    }

    /**
     * Check if chatbot should be loaded
     */
    private function shouldLoadChatbot(): bool {
        // Check if chatbot is enabled
        if (!get_option('ai_chatbot_rag_chatbot_enabled', true)) {
            return false;
        }

        // Check if API key is configured
        $apiKey = get_option('ai_chatbot_rag_deepseek_api_key', '');
        if (empty($apiKey)) {
            return false;
        }

        return true;
    }

    /**
     * Get or create session ID
     */
    private function getOrCreateSessionId(): string {
        $cookieName = 'ai_chatbot_session_id';

        if (isset($_COOKIE[$cookieName])) {
            return sanitize_text_field($_COOKIE[$cookieName]);
        }

        $sessionId = wp_generate_uuid4();
        setcookie($cookieName, $sessionId, time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);

        return $sessionId;
    }
}
