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
