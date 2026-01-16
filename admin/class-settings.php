<?php
/**
 * Settings Handler
 *
 * @package AIChatbotRAG
 */

namespace AIChatbotRAG\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Settings
 */
class Settings {

    public function __construct() {
        add_action('admin_init', [$this, 'registerSettings']);
    }

    /**
     * Register plugin settings
     */
    public function registerSettings(): void {
        // DeepSeek API Settings
        register_setting('ai_chatbot_rag_deepseek', 'ai_chatbot_rag_deepseek_api_key', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        register_setting('ai_chatbot_rag_deepseek', 'ai_chatbot_rag_deepseek_model', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'deepseek-chat',
        ]);

        register_setting('ai_chatbot_rag_deepseek', 'ai_chatbot_rag_temperature', [
            'type' => 'number',
            'sanitize_callback' => 'floatval',
            'default' => 0.3,
        ]);

        register_setting('ai_chatbot_rag_deepseek', 'ai_chatbot_rag_max_tokens', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 1000,
        ]);

        // Indexing Settings
        register_setting('ai_chatbot_rag_indexing', 'ai_chatbot_rag_chunk_size', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 500,
        ]);

        register_setting('ai_chatbot_rag_indexing', 'ai_chatbot_rag_chunk_overlap', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 50,
        ]);

        register_setting('ai_chatbot_rag_indexing', 'ai_chatbot_rag_enabled_post_types', [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitizePostTypes'],
            'default' => ['post', 'page'],
        ]);

        register_setting('ai_chatbot_rag_indexing', 'ai_chatbot_rag_exclude_categories', [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitizeCategories'],
            'default' => [],
        ]);

        // RAG Settings
        register_setting('ai_chatbot_rag_settings', 'ai_chatbot_rag_max_context_chunks', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 5,
        ]);

        register_setting('ai_chatbot_rag_settings', 'ai_chatbot_rag_conversation_history', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 5,
        ]);

        register_setting('ai_chatbot_rag_settings', 'ai_chatbot_rag_system_prompt', [
            'type' => 'string',
            'sanitize_callback' => 'wp_kses_post',
        ]);

        register_setting('ai_chatbot_rag_settings', 'ai_chatbot_rag_no_context_message', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
        ]);

        // Chatbot UI Settings
        register_setting('ai_chatbot_rag_ui', 'ai_chatbot_rag_chatbot_title', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => __('¿Cómo podemos ayudarte?', 'ai-chatbot-rag'),
        ]);

        register_setting('ai_chatbot_rag_ui', 'ai_chatbot_rag_chatbot_placeholder', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => __('Escribe tu pregunta...', 'ai-chatbot-rag'),
        ]);

        register_setting('ai_chatbot_rag_ui', 'ai_chatbot_rag_chatbot_enabled', [
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true,
        ]);

        register_setting('ai_chatbot_rag_ui', 'ai_chatbot_rag_button_position', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'bottom-right',
        ]);

        register_setting('ai_chatbot_rag_ui', 'ai_chatbot_rag_button_icon', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'chat',
        ]);

        register_setting('ai_chatbot_rag_ui', 'ai_chatbot_rag_primary_color', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_hex_color',
            'default' => '#CF142B',
        ]);

        register_setting('ai_chatbot_rag_ui', 'ai_chatbot_rag_bot_avatar', [
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => '',
        ]);

        register_setting('ai_chatbot_rag_ui', 'ai_chatbot_rag_bot_avatar_size', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 28,
        ]);
    }

    /**
     * Sanitize post types
     */
    public function sanitizePostTypes($value): array {
        if (!is_array($value)) {
            return ['post', 'page'];
        }

        return array_map('sanitize_text_field', $value);
    }

    /**
     * Sanitize categories
     */
    public function sanitizeCategories($value): array {
        if (!is_array($value)) {
            return [];
        }

        return array_map('absint', $value);
    }
}
