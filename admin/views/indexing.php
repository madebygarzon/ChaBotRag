<?php
/**
 * Indexing Page View
 *
 * @package AIChatbotRAG
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="ai-chatbot-indexing">
        <div class="ai-chatbot-card">
            <h2><?php _e('Content Indexing', 'ai-chatbot-rag'); ?></h2>
            <p><?php _e('Index your WordPress content to make it searchable for the chatbot.', 'ai-chatbot-rag'); ?></p>

            <div class="indexing-actions">
                <button type="button" id="ai-chatbot-index-btn" class="button button-primary button-large">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Start Indexing', 'ai-chatbot-rag'); ?>
                </button>

                <button type="button" id="ai-chatbot-debug-btn" class="button button-secondary">
                    <span class="dashicons dashicons-buddicons-activity"></span>
                    <?php _e('Debug Indexing', 'ai-chatbot-rag'); ?>
                </button>

                <button type="button" id="ai-chatbot-clear-btn" class="button button-secondary">
                    <span class="dashicons dashicons-trash"></span>
                    <?php _e('Clear All Data', 'ai-chatbot-rag'); ?>
                </button>
            </div>

            <div id="ai-chatbot-indexing-progress" style="display: none; margin-top: 20px;">
                <div class="progress-bar">
                    <div class="progress-fill"></div>
                </div>
                <p class="progress-text"></p>
            </div>

            <div id="ai-chatbot-indexing-result" style="margin-top: 20px;"></div>
        </div>

        <div class="ai-chatbot-card">
            <h2><?php _e('Selective Indexing Control', 'ai-chatbot-rag'); ?></h2>
            <p><?php _e('Choose which specific pages, posts, and content to include in the search index. This overrides the global post type settings.', 'ai-chatbot-rag'); ?></p>

            <div class="selective-indexing-controls">
                <div class="tab-container">
                    <ul class="tab-nav">
                        <li class="active" data-tab="posts"><?php _e('Posts', 'ai-chatbot-rag'); ?></li>
                        <li data-tab="pages"><?php _e('Pages', 'ai-chatbot-rag'); ?></li>
                        <li data-tab="custom"><?php _e('Custom Settings', 'ai-chatbot-rag'); ?></li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane active" id="posts-tab">
                            <div class="search-controls">
                                <input type="text" id="posts-search" placeholder="<?php _e('Search posts...', 'ai-chatbot-rag'); ?>" class="regular-text">
                                <select id="posts-per-page">
                                    <option value="20">20</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                                <button type="button" id="posts-search-btn" class="button"><?php _e('Search', 'ai-chatbot-rag'); ?></button>
                            </div>
                            <div id="posts-list" class="content-list">
                                <p class="loading"><?php _e('Loading posts...', 'ai-chatbot-rag'); ?></p>
                            </div>
                        </div>

                        <div class="tab-pane" id="pages-tab">
                            <div class="search-controls">
                                <input type="text" id="pages-search" placeholder="<?php _e('Search pages...', 'ai-chatbot-rag'); ?>" class="regular-text">
                                <select id="pages-per-page">
                                    <option value="20">20</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                                <button type="button" id="pages-search-btn" class="button"><?php _e('Search', 'ai-chatbot-rag'); ?></button>
                            </div>
                            <div id="pages-list" class="content-list">
                                <p class="loading"><?php _e('Loading pages...', 'ai-chatbot-rag'); ?></p>
                            </div>
                        </div>

                        <div class="tab-pane" id="custom-tab">
                            <div class="bulk-actions">
                                <select id="bulk-status">
                                    <option value=""><?php _e('Set status...', 'ai-chatbot-rag'); ?></option>
                                    <option value="auto"><?php _e('Auto (default)', 'ai-chatbot-rag'); ?></option>
                                    <option value="force_index"><?php _e('Force Index', 'ai-chatbot-rag'); ?></option>
                                    <option value="no_index"><?php _e('No Index', 'ai-chatbot-rag'); ?></option>
                                </select>
                                <button type="button" id="bulk-update-btn" class="button"><?php _e('Apply to Selected', 'ai-chatbot-rag'); ?></button>
                            </div>
                            <div id="custom-list" class="content-list">
                                <p class="loading"><?php _e('Loading custom settings...', 'ai-chatbot-rag'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="ai-chatbot-card">
            <h2><?php _e('Indexing Statistics', 'ai-chatbot-rag'); ?></h2>

            <table class="wp-list-table widefat striped">
                <tbody>
                    <tr>
                        <th><?php _e('Total Chunks', 'ai-chatbot-rag'); ?></th>
                        <td><?php echo number_format($stats['total_chunks'] ?? 0); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Total Embeddings', 'ai-chatbot-rag'); ?></th>
                        <td><?php echo number_format($stats['total_embeddings'] ?? 0); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Indexed Posts', 'ai-chatbot-rag'); ?></th>
                        <td><?php echo number_format($stats['indexed_posts'] ?? 0); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <?php if (!empty($stats['post_types'])) : ?>
        <div class="ai-chatbot-card">
            <h2><?php _e('Content Breakdown', 'ai-chatbot-rag'); ?></h2>

            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th><?php _e('Post Type', 'ai-chatbot-rag'); ?></th>
                        <th><?php _e('Chunks', 'ai-chatbot-rag'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['post_types'] as $type) : ?>
                    <tr>
                        <td><?php echo esc_html($type->post_type); ?></td>
                        <td><?php echo number_format($type->count); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div class="ai-chatbot-card">
            <h2><?php _e('How Indexing Works', 'ai-chatbot-rag'); ?></h2>
            <ol>
                <li><?php _e('Extracts content from posts, pages, and custom post types', 'ai-chatbot-rag'); ?></li>
                <li><?php _e('Removes HTML, shortcodes, and scripts', 'ai-chatbot-rag'); ?></li>
                <li><?php _e('Splits content into chunks with configurable size and overlap', 'ai-chatbot-rag'); ?></li>
                <li><?php _e('Generates embeddings for semantic search (MVP uses keyword matching)', 'ai-chatbot-rag'); ?></li>
                <li><?php _e('Stores everything in the database for fast retrieval', 'ai-chatbot-rag'); ?></li>
            </ol>

            <div class="notice notice-info inline">
                <p>
                    <strong><?php _e('Note:', 'ai-chatbot-rag'); ?></strong>
                    <?php _e('The MVP version uses keyword-based search with TF-IDF scoring. The architecture is prepared to migrate to proper vector embeddings with an external vector database.', 'ai-chatbot-rag'); ?>
                </p>
            </div>
        </div>
    </div>
</div>
