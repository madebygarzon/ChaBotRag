<?php
/**
 * Plugin Activator
 *
 * @package AIChatbotRAG
 */

namespace AIChatbotRAG;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Activator
 */
class Activator {

    /**
     * Activate plugin
     */
    public static function activate(): void {
        // Create database tables
        Database::createTables();

        // Set default options
        self::setDefaultOptions();

        // Schedule cron jobs for automatic re-indexing
        if (!wp_next_scheduled('ai_chatbot_rag_auto_index')) {
            wp_schedule_event(time(), 'daily', 'ai_chatbot_rag_auto_index');
        }

        // Flush rewrite rules
        flush_rewrite_rules();

        // Set activation flag
        update_option('ai_chatbot_rag_activated', true);
        update_option('ai_chatbot_rag_version', AI_CHATBOT_RAG_VERSION);
    }

    /**
     * Set default plugin options
     */
    private static function setDefaultOptions(): void {
        $defaults = [
            'ai_chatbot_rag_deepseek_api_key' => '',
            'ai_chatbot_rag_deepseek_model' => 'deepseek-chat',
            'ai_chatbot_rag_chunk_size' => 500,
            'ai_chatbot_rag_chunk_overlap' => 50,
            'ai_chatbot_rag_max_context_chunks' => 5,
            'ai_chatbot_rag_conversation_history' => 5,
            'ai_chatbot_rag_temperature' => 0.3,
            'ai_chatbot_rag_max_tokens' => 1000,
            'ai_chatbot_rag_enabled_post_types' => ['post', 'page'],
            'ai_chatbot_rag_exclude_categories' => [],
            'ai_chatbot_rag_chatbot_title' => __('How can we help you?', 'ai-chatbot-rag'),
            'ai_chatbot_rag_chatbot_placeholder' => __('Type your question...', 'ai-chatbot-rag'),
            'ai_chatbot_rag_system_prompt' => self::getDefaultSystemPrompt(),
            'ai_chatbot_rag_no_context_message' => __('I apologize, but I don\'t have specific information about that in our knowledge base. Please contact our team at https://partnerinpublishing.com/#brxe-8292d9 for personalized assistance.', 'ai-chatbot-rag'),
        ];

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                update_option($key, $value);
            }
        }
    }

    /**
     * Get default system prompt
     */
    private static function getDefaultSystemPrompt(): string {
        return <<<PROMPT
You are a professional virtual assistant for Partner in Publishing, a publishing services company.

IMPORTANT INSTRUCTIONS:
1. ALWAYS respond in English, regardless of the language used by the user.
2. ONLY answer using information from the CONTEXT below.
3. If the information is NOT in the context, politely invite the user to contact the team at: https://partnerinpublishing.com/#brxe-8292d9
4. DO NOT invent, assume, or add information not in the context.
5. Maintain a professional, helpful, and encouraging tone.
6. When appropriate, encourage visitors to reach out for personalized quotes or consultations.

CONTEXT:
{context}

CONTACT INVITATION (use when you don't have specific information):
"I'd be happy to help you with that! For personalized assistance, I invite you to contact our team directly through our contact form: https://partnerinpublishing.com/#brxe-8292d9

Our team will get back to you promptly to discuss your specific needs."

Remember: Always respond in English and guide visitors toward contacting Partner in Publishing when needed.
PROMPT;
    }
}
