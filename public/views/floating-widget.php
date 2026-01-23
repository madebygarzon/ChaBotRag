<?php
/**
 * Floating Chat Widget
 *
 * @package AIChatbotRAG
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- Floating Chat Button -->
<button id="ai-chatbot-floating-btn" class="ai-chatbot-floating-btn" data-position="<?php echo esc_attr($position); ?>" aria-label="Open chatbot">
    <span class="ai-chatbot-btn-icon" data-icon="<?php echo esc_attr($icon); ?>">
        <?php
        // SVG icons based on selected icon type
        $icons = [
            'chat' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"></path></svg>',
            'help' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
            'message' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>',
            'support' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 18v-6a9 9 0 0118 0v6"></path><path d="M21 19a2 2 0 01-2 2h-1a2 2 0 01-2-2v-3a2 2 0 012-2h3zM3 19a2 2 0 002 2h1a2 2 0 002-2v-3a2 2 0 00-2-2H3z"></path></svg>',
        ];
        echo $icons[$icon] ?? $icons['chat'];
        ?>
    </span>
    <span class="ai-chatbot-btn-close">×</span>
</button>

<!-- Floating Chat Window -->
<div id="ai-chatbot-floating-window" class="ai-chatbot-floating-window" style="display: none;">
    <div class="ai-chatbot-container ai-chatbot-floating">
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
                <h3><?php echo esc_html($title); ?></h3>
                <span class="ai-chatbot-status-indicator" title="Online">
                    <svg width="12" height="12" viewBox="0 0 12 12">
                        <circle cx="6" cy="6" r="5" fill="#10b981"/>
                    </svg>
                </span>
            </div>
            <button id="ai-chatbot-close-btn" class="ai-chatbot-close-btn" aria-label="Close chatbot">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                    <path d="M15 5L5 15M5 5l10 10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <div id="ai-chatbot-messages" class="ai-chatbot-messages"></div>

        <div class="ai-chatbot-input-container">
            <form id="ai-chatbot-form" class="ai-chatbot-form">
                <textarea
                    id="ai-chatbot-input"
                    class="ai-chatbot-input"
                    placeholder="<?php echo esc_attr($placeholder); ?>"
                    rows="1"
                    aria-label="Message input"
                ></textarea>
                <button
                    type="submit"
                    id="ai-chatbot-send-btn"
                    class="ai-chatbot-send-btn"
                    aria-label="Send message"
                >
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M18 2L9 11M18 2L12 18L9 11M18 2L2 8L9 11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </form>
            <div class="ai-chatbot-support-link">
                <p>Still having issues? Create a Support Ticket <a href="https://olihelp.zohodesk.com/portal/en/newticket" target="_blank" rel="noopener noreferrer">HERE</a></p>
            </div>
        </div>
    </div>
</div>
