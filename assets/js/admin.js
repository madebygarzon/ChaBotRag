/**
 * Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Index content button
        $('#ai-chatbot-index-btn').on('click', function() {
            const $btn = $(this);
            const $progress = $('#ai-chatbot-indexing-progress');
            const $result = $('#ai-chatbot-indexing-result');

            // Disable button and show progress
            $btn.prop('disabled', true).addClass('loading');
            $progress.show();
            $result.empty();

            $('.progress-text').text(aiChatbotRAG.i18n.indexing);
            $('.progress-fill').css('width', '50%');

            // Make AJAX request
            $.ajax({
                url: aiChatbotRAG.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'ai_chatbot_index_content',
                    nonce: aiChatbotRAG.nonce
                },
                success: function(response) {
                    $('.progress-fill').css('width', '100%');
                    $('.progress-text').text(aiChatbotRAG.i18n.indexingComplete);

                    if (response.success) {
                        const stats = response.data.stats;
                        let message = `<div class="notice notice-success">
                            <p><strong>${aiChatbotRAG.i18n.indexingComplete}</strong></p>
                            <ul>
                                <li>Total posts: ${stats.total_posts || 0}</li>
                                <li>Indexed posts: ${stats.indexed_posts || 0}</li>
                                <li>Total chunks: ${stats.total_chunks || 0}</li>
                                <li>Embeddings processed: ${stats.processed || 0}</li>
                            </ul>
                        </div>`;

                        if (stats.errors && stats.errors.length > 0) {
                            message += '<div class="notice notice-warning"><p><strong>Errors:</strong></p><ul>';
                            stats.errors.forEach(function(error) {
                                message += `<li>${error}</li>`;
                            });
                            message += '</ul></div>';
                        }

                        $result.html(message);

                        // Reload page after 2 seconds to update stats
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        $result.html(`<div class="notice notice-error"><p>${response.data.message}</p></div>`);
                    }
                },
                error: function(xhr, status, error) {
                    $('.progress-fill').css('width', '0%');
                    $('.progress-text').text('');
                    $result.html(`<div class="notice notice-error"><p>${aiChatbotRAG.i18n.indexingError}: ${error}</p></div>`);
                },
                complete: function() {
                    $btn.prop('disabled', false).removeClass('loading');
                    setTimeout(function() {
                        $progress.hide();
                    }, 2000);
                }
            });
        });

        // Clear data button
        $('#ai-chatbot-clear-btn').on('click', function() {
            if (!confirm(aiChatbotRAG.i18n.confirmClear)) {
                return;
            }

            const $btn = $(this);
            const $result = $('#ai-chatbot-indexing-result');

            $btn.prop('disabled', true).addClass('loading');
            $result.empty();

            $.ajax({
                url: aiChatbotRAG.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'ai_chatbot_clear_data',
                    nonce: aiChatbotRAG.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $result.html(`<div class="notice notice-success"><p>${response.data.message}</p></div>`);
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        $result.html(`<div class="notice notice-error"><p>${response.data.message}</p></div>`);
                    }
                },
                error: function(xhr, status, error) {
                    $result.html(`<div class="notice notice-error"><p>Error: ${error}</p></div>`);
                },
                complete: function() {
                    $btn.prop('disabled', false).removeClass('loading');
                }
            });
        });

        // Auto-resize textareas
        $('textarea').on('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        // Initialize color picker
        $('.color-picker').wpColorPicker();

        // Bot avatar size slider
        $('#bot_avatar_size').on('input', function() {
            $('#bot_avatar_size_value').text($(this).val() + 'px');
        });

        // Media uploader for bot avatar
        if (typeof wp !== 'undefined' && typeof wp.media !== 'undefined') {
            var mediaUploader;

            $('.ai-chatbot-upload-avatar-btn').on('click', function(e) {
                e.preventDefault();

                // If the uploader object has already been created, reopen the dialog
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }

                // Create the media uploader
                mediaUploader = wp.media({
                    title: 'Select Bot Avatar/Icon',
                    button: {
                        text: 'Use this image'
                    },
                    multiple: false,
                    library: {
                        type: ['image']
                    }
                });

                // When an image is selected, run a callback
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();

                    // Update the hidden input
                    $('#bot_avatar').val(attachment.url);

                    // Update the preview
                    $('.ai-chatbot-avatar-preview').html(
                        '<img src="' + attachment.url + '" style="max-width: 60px; max-height: 60px; border-radius: 8px;">'
                    );

                    // Show remove button
                    $('.ai-chatbot-remove-avatar-btn').show();
                });

                // Open the uploader
                mediaUploader.open();
            });
        } else {
            // Fallback if wp.media is not available
            $('.ai-chatbot-upload-avatar-btn').on('click', function(e) {
                e.preventDefault();
                alert('WordPress Media Library is not available. Please refresh the page.');
            });
        }

        // Remove avatar
        $('.ai-chatbot-remove-avatar-btn').on('click', function(e) {
            e.preventDefault();

            // Clear the hidden input
            $('#bot_avatar').val('');

            // Reset the preview
            $('.ai-chatbot-avatar-preview').html(
                '<div style="width: 60px; height: 60px; background: #f0f0f1; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #666;">No icon</div>'
            );

            // Hide remove button
            $(this).hide();
        });
    });

})(jQuery);
