<?php
/**
 * Settings Page View
 *
 * @package AIChatbotRAG
 */

if (!defined('ABSPATH')) {
    exit;
}

// Save settings
if (isset($_POST['ai_chatbot_rag_save_settings'])) {
    check_admin_referer('ai_chatbot_rag_settings');

    $sections = ['deepseek', 'indexing', 'settings', 'ui'];
    foreach ($sections as $section) {
        if (isset($_POST["ai_chatbot_rag_{$section}"])) {
            foreach ($_POST["ai_chatbot_rag_{$section}"] as $key => $value) {
                update_option($key, $value);
            }
        }
    }

    echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'ai-chatbot-rag') . '</p></div>';
}

?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field('ai_chatbot_rag_settings'); ?>

        <div class="ai-chatbot-settings">
            <!-- DeepSeek API Settings -->
            <div class="ai-chatbot-card">
                <h2><?php _e('DeepSeek API Configuration', 'ai-chatbot-rag'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="deepseek_api_key"><?php _e('API Key', 'ai-chatbot-rag'); ?></label>
                        </th>
                        <td>
                            <input type="password"
                                   id="deepseek_api_key"
                                   name="ai_chatbot_rag_deepseek[ai_chatbot_rag_deepseek_api_key]"
                                   value="<?php echo esc_attr(get_option('ai_chatbot_rag_deepseek_api_key', '')); ?>"
                                   class="regular-text">
                            <p class="description"><?php _e('Your DeepSeek API key. Get it from', 'ai-chatbot-rag'); ?> <a href="https://platform.deepseek.com/" target="_blank">platform.deepseek.com</a></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="deepseek_model"><?php _e('Model', 'ai-chatbot-rag'); ?></label>
                        </th>
                        <td>
                            <select id="deepseek_model" name="ai_chatbot_rag_deepseek[ai_chatbot_rag_deepseek_model]">
                                <option value="deepseek-chat" <?php selected(get_option('ai_chatbot_rag_deepseek_model', 'deepseek-chat'), 'deepseek-chat'); ?>>deepseek-chat</option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="temperature"><?php _e('Temperature', 'ai-chatbot-rag'); ?></label>
                        </th>
                        <td>
                            <input type="number"
                                   id="temperature"
                                   name="ai_chatbot_rag_deepseek[ai_chatbot_rag_temperature]"
                                   value="<?php echo esc_attr(get_option('ai_chatbot_rag_temperature', 0.3)); ?>"
                                   step="0.1"
                                   min="0"
                                   max="2"
                                   class="small-text">
                            <p class="description"><?php _e('Controls randomness. Lower = more focused, Higher = more creative. Recommended: 0.3', 'ai-chatbot-rag'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="max_tokens"><?php _e('Max Tokens', 'ai-chatbot-rag'); ?></label>
                        </th>
                        <td>
                            <input type="number"
                                   id="max_tokens"
                                   name="ai_chatbot_rag_deepseek[ai_chatbot_rag_max_tokens]"
                                   value="<?php echo esc_attr(get_option('ai_chatbot_rag_max_tokens', 1000)); ?>"
                                   min="100"
                                   max="4000"
                                   class="small-text">
                            <p class="description"><?php _e('Maximum tokens for response generation', 'ai-chatbot-rag'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Indexing Settings -->
            <div class="ai-chatbot-card">
                <h2><?php _e('Indexing Settings', 'ai-chatbot-rag'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="chunk_size"><?php _e('Chunk Size (words)', 'ai-chatbot-rag'); ?></label>
                        </th>
                        <td>
                            <input type="number"
                                   id="chunk_size"
                                   name="ai_chatbot_rag_indexing[ai_chatbot_rag_chunk_size]"
                                   value="<?php echo esc_attr(get_option('ai_chatbot_rag_chunk_size', 500)); ?>"
                                   min="100"
                                   max="2000"
                                   class="small-text">
                            <p class="description"><?php _e('Number of words per chunk. Recommended: 500', 'ai-chatbot-rag'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="chunk_overlap"><?php _e('Chunk Overlap (words)', 'ai-chatbot-rag'); ?></label>
                        </th>
                        <td>
                            <input type="number"
                                   id="chunk_overlap"
                                   name="ai_chatbot_rag_indexing[ai_chatbot_rag_chunk_overlap]"
                                   value="<?php echo esc_attr(get_option('ai_chatbot_rag_chunk_overlap', 50)); ?>"
                                   min="0"
                                   max="200"
                                   class="small-text">
                            <p class="description"><?php _e('Words to overlap between chunks. Recommended: 50', 'ai-chatbot-rag'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label><?php _e('Post Types to Index', 'ai-chatbot-rag'); ?></label>
                        </th>
                        <td>
                            <?php
                            $postTypes = get_post_types(['public' => true], 'objects');
                            $enabledTypes = get_option('ai_chatbot_rag_enabled_post_types', ['post', 'page']);

                            foreach ($postTypes as $postType) {
                                $checked = in_array($postType->name, $enabledTypes) ? 'checked' : '';
                                echo sprintf(
                                    '<label><input type="checkbox" name="ai_chatbot_rag_indexing[ai_chatbot_rag_enabled_post_types][]" value="%s" %s> %s</label><br>',
                                    esc_attr($postType->name),
                                    $checked,
                                    esc_html($postType->label)
                                );
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- RAG Settings -->
            <div class="ai-chatbot-card">
                <h2><?php _e('RAG Settings', 'ai-chatbot-rag'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="max_context_chunks"><?php _e('Max Context Chunks', 'ai-chatbot-rag'); ?></label>
                        </th>
                        <td>
                            <input type="number"
                                   id="max_context_chunks"
                                   name="ai_chatbot_rag_settings[ai_chatbot_rag_max_context_chunks]"
                                   value="<?php echo esc_attr(get_option('ai_chatbot_rag_max_context_chunks', 5)); ?>"
                                   min="1"
                                   max="10"
                                   class="small-text">
                            <p class="description"><?php _e('Maximum number of relevant chunks to include in context', 'ai-chatbot-rag'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="conversation_history"><?php _e('Conversation History', 'ai-chatbot-rag'); ?></label>
                        </th>
                        <td>
                            <input type="number"
                                   id="conversation_history"
                                   name="ai_chatbot_rag_settings[ai_chatbot_rag_conversation_history]"
                                   value="<?php echo esc_attr(get_option('ai_chatbot_rag_conversation_history', 5)); ?>"
                                   min="0"
                                   max="20"
                                   class="small-text">
                            <p class="description"><?php _e('Number of previous messages to include in conversation context', 'ai-chatbot-rag'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="system_prompt"><?php _e('System Prompt', 'ai-chatbot-rag'); ?></label>
                        </th>
                        <td>
                            <textarea id="system_prompt"
                                      name="ai_chatbot_rag_settings[ai_chatbot_rag_system_prompt]"
                                      rows="10"
                                      class="large-text"><?php echo esc_textarea(get_option('ai_chatbot_rag_system_prompt', '')); ?></textarea>
                            <p class="description"><?php _e('System prompt template. Use {context} placeholder for retrieved content.', 'ai-chatbot-rag'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="no_context_message"><?php _e('No Context Message', 'ai-chatbot-rag'); ?></label>
                        </th>
                        <td>
                            <textarea id="no_context_message"
                                      name="ai_chatbot_rag_settings[ai_chatbot_rag_no_context_message]"
                                      rows="3"
                                      class="large-text"><?php echo esc_textarea(get_option('ai_chatbot_rag_no_context_message', '')); ?></textarea>
                            <p class="description"><?php _e('Message to show when no relevant context is found', 'ai-chatbot-rag'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Chatbot UI Settings -->
            <div class="ai-chatbot-card">
                <h2><?php _e('Chatbot UI', 'ai-chatbot-rag'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="chatbot_enabled"><?php _e('Enable Chatbot', 'ai-chatbot-rag'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox"
                                   id="chatbot_enabled"
                                   name="ai_chatbot_rag_ui[ai_chatbot_rag_chatbot_enabled]"
                                   value="1"
                                   <?php checked(get_option('ai_chatbot_rag_chatbot_enabled', true)); ?>>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="chatbot_title"><?php _e('Chatbot Title', 'ai-chatbot-rag'); ?></label>
                        </th>
                        <td>
                            <input type="text"
                                   id="chatbot_title"
                                   name="ai_chatbot_rag_ui[ai_chatbot_rag_chatbot_title]"
                                   value="<?php echo esc_attr(get_option('ai_chatbot_rag_chatbot_title', '')); ?>"
                                   class="regular-text">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="chatbot_placeholder"><?php _e('Input Placeholder', 'ai-chatbot-rag'); ?></label>
                        </th>
                        <td>
                            <input type="text"
                                   id="chatbot_placeholder"
                                   name="ai_chatbot_rag_ui[ai_chatbot_rag_chatbot_placeholder]"
                                   value="<?php echo esc_attr(get_option('ai_chatbot_rag_chatbot_placeholder', '')); ?>"
                                   class="regular-text">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="chatbot_greeting"><?php _e('Greeting Message', 'ai-chatbot-rag'); ?></label>
                        </th>
                        <td>
                            <textarea id="chatbot_greeting"
                                      name="ai_chatbot_rag_ui[ai_chatbot_rag_chatbot_greeting]"
                                      rows="3"
                                      class="large-text"><?php echo esc_textarea(get_option('ai_chatbot_rag_chatbot_greeting', '¡Hola! ¿En qué puedo ayudarte hoy?')); ?></textarea>
                            <p class="description"><?php _e('Message to display when the chat opens. Leave empty to disable.', 'ai-chatbot-rag'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="chatbot_quick_actions"><?php _e('Quick Actions', 'ai-chatbot-rag'); ?></label>
                        </th>
                        <td>
                            <div id="quick-actions-container">
                                <p class="description"><?php _e('Add up to 6 quick action buttons that users can click to ask common questions.', 'ai-chatbot-rag'); ?></p>
                                
                                <div id="quick-actions-list">
                                    <?php
                                    $quickActions = get_option('ai_chatbot_rag_quick_actions', [
                                        '¿Qué productos ofrecen?',
                                        '¿Cuáles son sus horarios?',
                                        '¿Cómo contactarlos?'
                                    ]);
                                    
                                    if (!is_array($quickActions)) {
                                        $quickActions = [];
                                    }
                                    
                                    foreach ($quickActions as $index => $action) {
                                        echo '<div class="quick-action-item">';
                                        echo '<input type="text" name="ai_chatbot_rag_ui[ai_chatbot_rag_quick_actions][]" value="' . esc_attr($action) . '" class="regular-text" placeholder="' . esc_attr__('Enter quick action text', 'ai-chatbot-rag') . '">';
                                        echo '<button type="button" class="button button-small remove-quick-action">' . __('Remove', 'ai-chatbot-rag') . '</button>';
                                        echo '</div>';
                                    }
                                    
                                    // Add empty slots up to 6
                                    for ($i = count($quickActions); $i < 6; $i++) {
                                        echo '<div class="quick-action-item" style="display:none;">';
                                        echo '<input type="text" name="ai_chatbot_rag_ui[ai_chatbot_rag_quick_actions][]" value="" class="regular-text" placeholder="' . esc_attr__('Enter quick action text', 'ai-chatbot-rag') . '">';
                                        echo '<button type="button" class="button button-small remove-quick-action">' . __('Remove', 'ai-chatbot-rag') . '</button>';
                                        echo '</div>';
                                    }
                                    ?>
                                </div>
                                
                                <p>
                                    <button type="button" id="add-quick-action" class="button">
                                        <?php _e('Add Quick Action', 'ai-chatbot-rag'); ?>
                                    </button>
                                </p>
                                
                                <p class="description">
                                    <?php _e('Tip: Use clear, concise questions that users commonly ask. Maximum 6 quick actions.', 'ai-chatbot-rag'); ?>
                                </p>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="button_position"><?php _e('Button Position', 'ai-chatbot-rag'); ?></label>
                        </th>
                        <td>
                            <select id="button_position" name="ai_chatbot_rag_ui[ai_chatbot_rag_button_position]">
                                <option value="bottom-right" <?php selected(get_option('ai_chatbot_rag_button_position', 'bottom-right'), 'bottom-right'); ?>><?php _e('Bottom Right', 'ai-chatbot-rag'); ?></option>
                                <option value="bottom-left" <?php selected(get_option('ai_chatbot_rag_button_position', 'bottom-right'), 'bottom-left'); ?>><?php _e('Bottom Left', 'ai-chatbot-rag'); ?></option>
                                <option value="middle-right" <?php selected(get_option('ai_chatbot_rag_button_position', 'bottom-right'), 'middle-right'); ?>><?php _e('Middle Right', 'ai-chatbot-rag'); ?></option>
                                <option value="middle-left" <?php selected(get_option('ai_chatbot_rag_button_position', 'bottom-right'), 'middle-left'); ?>><?php _e('Middle Left', 'ai-chatbot-rag'); ?></option>
                                <option value="top-right" <?php selected(get_option('ai_chatbot_rag_button_position', 'bottom-right'), 'top-right'); ?>><?php _e('Top Right', 'ai-chatbot-rag'); ?></option>
                                <option value="top-left" <?php selected(get_option('ai_chatbot_rag_button_position', 'bottom-right'), 'top-left'); ?>><?php _e('Top Left', 'ai-chatbot-rag'); ?></option>
                            </select>
                            <p class="description"><?php _e('Position of the floating chat button', 'ai-chatbot-rag'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="button_icon"><?php _e('Button Icon', 'ai-chatbot-rag'); ?></label>
                        </th>
                        <td>
                            <select id="button_icon" name="ai_chatbot_rag_ui[ai_chatbot_rag_button_icon]">
                                <option value="chat" <?php selected(get_option('ai_chatbot_rag_button_icon', 'chat'), 'chat'); ?>><?php _e('Chat Bubble', 'ai-chatbot-rag'); ?></option>
                                <option value="help" <?php selected(get_option('ai_chatbot_rag_button_icon', 'chat'), 'help'); ?>><?php _e('Help/Question Mark', 'ai-chatbot-rag'); ?></option>
                                <option value="message" <?php selected(get_option('ai_chatbot_rag_button_icon', 'chat'), 'message'); ?>><?php _e('Message', 'ai-chatbot-rag'); ?></option>
                                <option value="support" <?php selected(get_option('ai_chatbot_rag_button_icon', 'chat'), 'support'); ?>><?php _e('Support/Headset', 'ai-chatbot-rag'); ?></option>
                            </select>
                            <p class="description"><?php _e('Icon to display on the floating button', 'ai-chatbot-rag'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="primary_color"><?php _e('Primary Color', 'ai-chatbot-rag'); ?></label>
                        </th>
                        <td>
                            <input type="text"
                                   id="primary_color"
                                   name="ai_chatbot_rag_ui[ai_chatbot_rag_primary_color]"
                                   value="<?php echo esc_attr(get_option('ai_chatbot_rag_primary_color', '#CF142B')); ?>"
                                   class="color-picker">
                            <p class="description"><?php _e('Primary color for the chatbot (header, buttons, etc.)', 'ai-chatbot-rag'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="bot_avatar"><?php _e('Bot Avatar/Icon', 'ai-chatbot-rag'); ?></label>
                        </th>
                        <td>
                            <div class="ai-chatbot-avatar-upload">
                                <input type="hidden"
                                       id="bot_avatar"
                                       name="ai_chatbot_rag_ui[ai_chatbot_rag_bot_avatar]"
                                       value="<?php echo esc_attr(get_option('ai_chatbot_rag_bot_avatar', '')); ?>">
                                <div class="ai-chatbot-avatar-preview">
                                    <?php
                                    $avatarUrl = get_option('ai_chatbot_rag_bot_avatar', '');
                                    if ($avatarUrl) {
                                        echo '<img src="' . esc_url($avatarUrl) . '" style="max-width: 60px; max-height: 60px; border-radius: 8px;">';
                                    } else {
                                        echo '<div style="width: 60px; height: 60px; background: #f0f0f1; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #666;">No icon</div>';
                                    }
                                    ?>
                                </div>
                                <p>
                                    <button type="button" class="button ai-chatbot-upload-avatar-btn">
                                        <?php _e('Upload Icon', 'ai-chatbot-rag'); ?>
                                    </button>
                                    <button type="button" class="button ai-chatbot-remove-avatar-btn" <?php echo empty($avatarUrl) ? 'style="display:none;"' : ''; ?>>
                                        <?php _e('Remove Icon', 'ai-chatbot-rag'); ?>
                                    </button>
                                </p>
                                <p class="description">
                                    <?php _e('Upload a custom icon/avatar for your chatbot (recommended size: 64x64px, PNG or SVG)', 'ai-chatbot-rag'); ?>
                                </p>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="bot_avatar_size"><?php _e('Bot Icon Size', 'ai-chatbot-rag'); ?></label>
                        </th>
                        <td>
                            <input type="range"
                                   id="bot_avatar_size"
                                   name="ai_chatbot_rag_ui[ai_chatbot_rag_bot_avatar_size]"
                                   value="<?php echo esc_attr(get_option('ai_chatbot_rag_bot_avatar_size', 28)); ?>"
                                   min="16"
                                   max="48"
                                   step="2"
                                   style="width: 300px; vertical-align: middle;">
                            <span id="bot_avatar_size_value" style="margin-left: 10px; font-weight: bold;">
                                <?php echo esc_html(get_option('ai_chatbot_rag_bot_avatar_size', 28)); ?>px
                            </span>
                            <p class="description"><?php _e('Adjust the size of the bot icon in the chat header (16px - 48px)', 'ai-chatbot-rag'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <?php submit_button(__('Save Settings', 'ai-chatbot-rag'), 'primary', 'ai_chatbot_rag_save_settings'); ?>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    let quickActionCount = $('.quick-action-item').filter(function() {
        return $(this).find('input').val() !== '';
    }).length;
    
    // Show existing quick actions
    $('.quick-action-item input').each(function() {
        if ($(this).val() !== '') {
            $(this).closest('.quick-action-item').show();
            quickActionCount++;
        }
    });
    
    // Add quick action
    $('#add-quick-action').on('click', function() {
        if (quickActionCount >= 6) {
            alert('<?php _e('Maximum 6 quick actions allowed.', 'ai-chatbot-rag'); ?>');
            return;
        }
        
        const $hiddenItem = $('.quick-action-item:hidden:first');
        if ($hiddenItem.length) {
            $hiddenItem.show();
            quickActionCount++;
            updateAddButton();
        }
    });
    
    // Remove quick action
    $(document).on('click', '.remove-quick-action', function() {
        const $item = $(this).closest('.quick-action-item');
        $item.find('input').val('');
        $item.hide();
        quickActionCount--;
        updateAddButton();
    });
    
    function updateAddButton() {
        $('#add-quick-action').prop('disabled', quickActionCount >= 6);
        if (quickActionCount >= 6) {
            $('#add-quick-action').text('<?php _e('Maximum Quick Actions Reached', 'ai-chatbot-rag'); ?>');
        } else {
            $('#add-quick-action').text('<?php _e('Add Quick Action', 'ai-chatbot-rag'); ?>');
        }
    }
    
    updateAddButton();
});
</script>
