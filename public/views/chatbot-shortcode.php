<?php
/**
 * Chatbot Shortcode Template
 *
 * @package AIChatbotRAG
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="ai-chatbot-container" style="height: <?php echo esc_attr($atts['height']); ?>;">
    <div class="ai-chatbot-header">
        <div class="ai-chatbot-header-left">
            <?php
            $botAvatar = get_option('ai_chatbot_rag_bot_avatar', '');
            if (!empty($botAvatar)) {
                echo '<img src="' . esc_url($botAvatar) . '" class="ai-chatbot-bot-icon ai-chatbot-bot-avatar" alt="Bot icon">';
            } else {
                echo '<svg class="ai-chatbot-bot-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="10" rx="2"></rect>
                    <circle cx="8" cy="15" r="1"></circle>
                    <circle cx="16" cy="15" r="1"></circle>
                    <path d="M9 7V8"></path>
                    <path d="M15 7V8"></path>
                    <path d="M12 3v5"></path>
                </svg>';
            }
            ?>
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <span class="ai-chatbot-status-indicator" title="Online">
                <svg width="12" height="12" viewBox="0 0 12 12">
                    <circle cx="6" cy="6" r="5" fill="#10b981"/>
                </svg>
            </span>
        </div>
    </div>

    <div class="ai-chatbot-messages" id="ai-chatbot-messages">
        <div class="ai-chatbot-message ai-chatbot-message-assistant">
            <div class="ai-chatbot-message-content">
                <?php echo esc_html($atts['title']); ?>
            </div>
        </div>
    </div>

    <div class="ai-chatbot-input-container">
        <form id="ai-chatbot-form" class="ai-chatbot-form">
            <textarea
                id="ai-chatbot-input"
                class="ai-chatbot-input"
                placeholder="<?php echo esc_attr(get_option('ai_chatbot_rag_chatbot_placeholder', __('Escribe tu pregunta...', 'ai-chatbot-rag'))); ?>"
                rows="1"
            ></textarea>
            <button type="submit" class="ai-chatbot-send-btn" id="ai-chatbot-send-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="22" y1="2" x2="11" y2="13"></line>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                </svg>
            </button>
        </form>
    </div>
</div>
