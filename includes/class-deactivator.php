<?php
/**
 * Plugin Deactivator
 *
 * @package AIChatbotRAG
 */

namespace AIChatbotRAG;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Deactivator
 */
class Deactivator {

    /**
     * Deactivate plugin
     */
    public static function deactivate(): void {
        // Clear scheduled events
        $timestamp = wp_next_scheduled('ai_chatbot_rag_auto_index');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'ai_chatbot_rag_auto_index');
        }

        // Flush rewrite rules
        flush_rewrite_rules();
    }
}
