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

        // Debug indexing button
        $('#ai-chatbot-debug-btn').on('click', function() {
            const $btn = $(this);
            const $result = $('#ai-chatbot-indexing-result');

            $btn.prop('disabled', true).addClass('loading');
            $result.empty();

            $.ajax({
                url: aiChatbotRAG.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'ai_chatbot_debug_indexing',
                    nonce: aiChatbotRAG.nonce
                },
                success: function(response) {
                    if (response.success) {
                        let message = '<div class="notice notice-success">';
                        message += '<p><strong>Debug Complete!</strong></p>';
                        message += '<p><strong>Test Post:</strong> ' + response.data.debug_info.post.title + ' (ID: ' + response.data.debug_info.post.ID + ')</p>';
                        message += '<p><strong>Bricks Data Exists:</strong> ' + (response.data.debug_info.bricks_data_exists ? 'Yes' : 'No') + '</p>';
                        message += '<p><strong>Content Length:</strong> ' + response.data.debug_info.post_content_length + ' characters</p>';
                        message += '<p><strong>Extracted Content Preview:</strong></p>';
                        message += '<pre style="background: #f1f1f1; padding: 10px; max-height: 200px; overflow-y: auto;">' + 
                                   response.data.debug_info.extracted_content.substring(0, 500) + '...' + '</pre>';
                        message += '<p><strong>Bricks Content Preview:</strong></p>';
                        message += '<pre style="background: #f1f1f1; padding: 10px; max-height: 200px; overflow-y: auto;">' + 
                                   response.data.debug_info.bricks_content.substring(0, 500) + '...' + '</pre>';
                        message += '</div>';
                        $result.html(message);
                    } else {
                        $result.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                    }
                },
                error: function(xhr, status, error) {
                    $result.html('<div class="notice notice-error"><p>Debug error: ' + error + '</p></div>');
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

        // Selective Indexing UI
        initSelectiveIndexing();
    });

    function initSelectiveIndexing() {
        // Tab navigation
        $('.tab-nav li').on('click', function() {
            $('.tab-nav li').removeClass('active');
            $(this).addClass('active');
            
            const tabId = $(this).data('tab');
            $('.tab-pane').removeClass('active');
            $(`#${tabId}-tab`).addClass('active');
            
            // Load content for the active tab
            loadTabContent(tabId);
        });

        // Load initial content
        loadTabContent('posts');
    }

    function loadTabContent(tab) {
        switch (tab) {
            case 'posts':
                loadPosts();
                break;
            case 'pages':
                loadPages();
                break;
            case 'custom':
                loadCustomSettings();
                break;
        }
    }

    function loadPosts(search = '', perPage = 20) {
        $('#posts-list').html('<p class="loading">Loading posts...</p>');
        
        $.ajax({
            url: aiChatbotRAG.ajaxUrl,
            method: 'POST',
            data: {
                action: 'ai_chatbot_get_posts_for_indexing',
                search: search,
                per_page: perPage,
                nonce: aiChatbotRAG.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderContentList('posts', response.data.posts, response.data.statuses);
                } else {
                    $('#posts-list').html(`<div class="notice notice-error"><p>${response.data.message}</p></div>`);
                }
            },
            error: function() {
                $('#posts-list').html('<div class="notice notice-error"><p>Error loading posts.</p></div>');
            }
        });
    }

    function loadPages(search = '', perPage = 20) {
        $('#pages-list').html('<p class="loading">Loading pages...</p>');
        
        $.ajax({
            url: aiChatbotRAG.ajaxUrl,
            method: 'POST',
            data: {
                action: 'ai_chatbot_get_pages_for_indexing',
                search: search,
                per_page: perPage,
                nonce: aiChatbotRAG.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderContentList('pages', response.data.pages, response.data.statuses);
                } else {
                    $('#pages-list').html(`<div class="notice notice-error"><p>${response.data.message}</p></div>`);
                }
            },
            error: function() {
                $('#pages-list').html('<div class="notice notice-error"><p>Error loading pages.</p></div>');
            }
        });
    }

    function loadCustomSettings() {
        $('#custom-list').html('<p class="loading">Loading custom settings...</p>');
        
        $.ajax({
            url: aiChatbotRAG.ajaxUrl,
            method: 'POST',
            data: {
                action: 'ai_chatbot_get_custom_indexing_settings',
                nonce: aiChatbotRAG.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderCustomSettingsList(response.data.settings);
                } else {
                    $('#custom-list').html(`<div class="notice notice-error"><p>${response.data.message}</p></div>`);
                }
            },
            error: function() {
                $('#custom-list').html('<div class="notice notice-error"><p>Error loading custom settings.</p></div>');
            }
        });
    }

    function renderContentList(type, items, statuses) {
        const container = $(`#${type}-list`);
        if (items.length === 0) {
            container.html('<p>No items found.</p>');
            return;
        }

        let html = '<div class="content-items"><table class="wp-list-table widefat striped"><thead><tr>';
        html += '<th><input type="checkbox" id="select-all-' + type + '"></th>';
        html += '<th>Title</th>';
        html += '<th>Status</th>';
        html += '<th>Modified</th>';
        html += '<th>Actions</th>';
        html += '</tr></thead><tbody>';

        items.forEach(function(item) {
            const status = statuses[item.ID] || 'auto';
            const statusText = getStatusText(status);
            const statusClass = getStatusClass(status);
            
            html += '<tr>';
            html += '<td><input type="checkbox" class="item-select" data-id="' + item.ID + '" data-type="' + item.post_type + '"></td>';
            html += '<td><strong>' + item.post_title + '</strong><br><small>ID: ' + item.ID + '</small></td>';
            html += '<td><span class="status-badge ' + statusClass + '">' + statusText + '</span></td>';
            html += '<td>' + item.post_modified + '</td>';
            html += '<td>';
            html += '<select class="status-select" data-id="' + item.ID + '" data-type="' + item.post_type + '">';
            html += '<option value="auto" ' + (status === 'auto' ? 'selected' : '') + '>Auto</option>';
            html += '<option value="force_index" ' + (status === 'force_index' ? 'selected' : '') + '>Force Index</option>';
            html += '<option value="no_index" ' + (status === 'no_index' ? 'selected' : '') + '>No Index</option>';
            html += '</select>';
            html += '</td>';
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        container.html(html);

        // Event handlers
        $('#select-all-' + type).on('change', function() {
            $('.item-select').prop('checked', $(this).prop('checked'));
        });

        $('.status-select').on('change', function() {
            const $select = $(this);
            const postId = $select.data('id');
            const postType = $select.data('type');
            const status = $select.val();
            
            updatePostIndexingStatus(postId, postType, status, function(success) {
                if (success) {
                    const $statusBadge = $select.closest('tr').find('.status-badge');
                    $statusBadge.removeClass('status-auto status-force-index status-no-index')
                                 .addClass('status-' + status)
                                 .text(getStatusText(status));
                } else {
                    alert('Error updating status');
                    $select.val(statuses[postId] || 'auto');
                }
            });
        });
    }

    function renderCustomSettingsList(settings) {
        const container = $('#custom-list');
        if (settings.length === 0) {
            container.html('<p>No custom indexing settings found.</p>');
            return;
        }

        let html = '<div class="custom-items"><table class="wp-list-table widefat striped"><thead><tr>';
        html += '<th><input type="checkbox" id="select-all-custom"></th>';
        html += '<th>Title</th>';
        html += '<th>Type</th>';
        html += '<th>Status</th>';
        html += '<th>Updated</th>';
        html += '<th>Actions</th>';
        html += '</tr></thead><tbody>';

        settings.forEach(function(item) {
            const statusText = getStatusText(item.index_status);
            const statusClass = getStatusClass(item.index_status);
            
            html += '<tr>';
            html += '<td><input type="checkbox" class="custom-select" data-id="' + item.post_id + '" data-type="' + item.post_type + '"></td>';
            html += '<td><strong>' + (item.post_title || 'Post ID: ' + item.post_id) + '</strong></td>';
            html += '<td>' + item.post_type + '</td>';
            html += '<td><span class="status-badge ' + statusClass + '">' + statusText + '</span></td>';
            html += '<td>' + item.updated_at + '</td>';
            html += '<td><button type="button" class="button button-small clear-status" data-id="' + item.post_id + '" data-type="' + item.post_type + '">Clear</button></td>';
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        container.html(html);

        // Event handlers
        $('#select-all-custom').on('change', function() {
            $('.custom-select').prop('checked', $(this).prop('checked'));
        });

        $('.clear-status').on('click', function() {
            const $btn = $(this);
            const postId = $btn.data('id');
            const postType = $btn.data('type');
            
            if (confirm('Clear indexing status for this item?')) {
                clearPostIndexingStatus(postId, postType, function(success) {
                    if (success) {
                        $btn.closest('tr').fadeOut(400, function() {
                            $(this).remove();
                        });
                    } else {
                        alert('Error clearing status');
                    }
                });
            }
        });
    }

    function getStatusText(status) {
        const statusMap = {
            'auto': 'Auto (default)',
            'force_index': 'Force Index',
            'no_index': 'No Index'
        };
        return statusMap[status] || status;
    }

    function getStatusClass(status) {
        return 'status-' + status;
    }

    function updatePostIndexingStatus(postId, postType, status, callback) {
        $.ajax({
            url: aiChatbotRAG.ajaxUrl,
            method: 'POST',
            data: {
                action: 'ai_chatbot_update_indexing_status',
                post_id: postId,
                post_type: postType,
                status: status,
                nonce: aiChatbotRAG.nonce
            },
            success: function(response) {
                callback(response.success);
            },
            error: function() {
                callback(false);
            }
        });
    }

    function clearPostIndexingStatus(postId, postType, callback) {
        $.ajax({
            url: aiChatbotRAG.ajaxUrl,
            method: 'POST',
            data: {
                action: 'ai_chatbot_clear_indexing_status',
                post_id: postId,
                post_type: postType,
                nonce: aiChatbotRAG.nonce
            },
            success: function(response) {
                callback(response.success);
            },
            error: function() {
                callback(false);
            }
        });
    }

    // Search functionality
    $('#posts-search-btn').on('click', function() {
        const search = $('#posts-search').val();
        const perPage = $('#posts-per-page').val();
        loadPosts(search, perPage);
    });

    $('#pages-search-btn').on('click', function() {
        const search = $('#pages-search').val();
        const perPage = $('#pages-per-page').val();
        loadPages(search, perPage);
    });

    // Bulk update functionality
    $('#bulk-update-btn').on('click', function() {
        const status = $('#bulk-status').val();
        if (!status) {
            alert('Please select a status to apply.');
            return;
        }

        const selected = $('.custom-select:checked');
        if (selected.length === 0) {
            alert('Please select at least one item.');
            return;
        }

        const updates = [];
        selected.each(function() {
            updates.push({
                id: $(this).data('id'),
                type: $(this).data('type')
            });
        });

        bulkUpdateStatus(updates, status);
    });

    function bulkUpdateStatus(updates, status) {
        const $btn = $('#bulk-update-btn');
        $btn.prop('disabled', true).text('Updating...');

        $.ajax({
            url: aiChatbotRAG.ajaxUrl,
            method: 'POST',
            data: {
                action: 'ai_chatbot_bulk_update_indexing_status',
                updates: updates,
                status: status,
                nonce: aiChatbotRAG.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(`Updated ${response.data.updated} items successfully.`);
                    loadCustomSettings(); // Refresh the list
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function() {
                alert('Error updating items.');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Apply to Selected');
            }
        });
    }

})(jQuery);
