<?php
/**
 * Admin Dashboard View
 *
 * @package AIChatbotRAG
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="ai-chatbot-dashboard">
        <div class="ai-chatbot-card">
            <h2><?php _e('Welcome to AI Chatbot RAG', 'ai-chatbot-rag'); ?></h2>
            <p><?php _e('Este plugin implementa un chatbot inteligente basado en el contenido de tu sitio web usando tecnología RAG (Retrieval-Augmented Generation) con DeepSeek.', 'ai-chatbot-rag'); ?></p>
        </div>

        <div class="ai-chatbot-stats">
            <div class="ai-chatbot-stat-card">
                <div class="stat-number"><?php echo number_format($stats['total_chunks'] ?? 0); ?></div>
                <div class="stat-label"><?php _e('Total Chunks', 'ai-chatbot-rag'); ?></div>
            </div>

            <div class="ai-chatbot-stat-card">
                <div class="stat-number"><?php echo number_format($stats['indexed_posts'] ?? 0); ?></div>
                <div class="stat-label"><?php _e('Indexed Posts', 'ai-chatbot-rag'); ?></div>
            </div>

            <div class="ai-chatbot-stat-card">
                <div class="stat-number"><?php echo number_format($stats['total_embeddings'] ?? 0); ?></div>
                <div class="stat-label"><?php _e('Embeddings', 'ai-chatbot-rag'); ?></div>
            </div>
        </div>

        <div class="ai-chatbot-card">
            <h2><?php _e('Quick Start', 'ai-chatbot-rag'); ?></h2>
            <ol>
                <li>
                    <strong><?php _e('Configure DeepSeek API:', 'ai-chatbot-rag'); ?></strong>
                    <a href="<?php echo admin_url('admin.php?page=ai-chatbot-rag-settings'); ?>"><?php _e('Go to Settings', 'ai-chatbot-rag'); ?></a>
                </li>
                <li>
                    <strong><?php _e('Index your content:', 'ai-chatbot-rag'); ?></strong>
                    <a href="<?php echo admin_url('admin.php?page=ai-chatbot-rag-indexing'); ?>"><?php _e('Go to Indexing', 'ai-chatbot-rag'); ?></a>
                </li>
                <li>
                    <strong><?php _e('Customize chatbot appearance:', 'ai-chatbot-rag'); ?></strong>
                    <a href="<?php echo admin_url('admin.php?page=ai-chatbot-rag-settings'); ?>"><?php _e('Configure button position, icon, and colors', 'ai-chatbot-rag'); ?></a>
                </li>
            </ol>
            <p style="margin-top: 15px; padding: 10px; background: #f0f0f1; border-left: 4px solid #CF142B;">
                <strong><?php _e('The floating chatbot button will appear automatically on all pages of your site.', 'ai-chatbot-rag'); ?></strong>
            </p>
        </div>

        <?php if (!empty($stats['post_types'])) : ?>
        <div class="ai-chatbot-card">
            <h2><?php _e('Indexed Content by Type', 'ai-chatbot-rag'); ?></h2>
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
            <h2><?php _e('System Status', 'ai-chatbot-rag'); ?></h2>
            <table class="wp-list-table widefat striped">
                <tbody>
                    <tr>
                        <td><?php _e('Plugin Version', 'ai-chatbot-rag'); ?></td>
                        <td><?php echo AI_CHATBOT_RAG_VERSION; ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('PHP Version', 'ai-chatbot-rag'); ?></td>
                        <td><?php echo phpversion(); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('WordPress Version', 'ai-chatbot-rag'); ?></td>
                        <td><?php echo get_bloginfo('version'); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('DeepSeek API Key', 'ai-chatbot-rag'); ?></td>
                        <td>
                            <?php
                            $apiKey = get_option('ai_chatbot_rag_deepseek_api_key', '');
                            if (!empty($apiKey)) {
                                echo '<span class="dashicons dashicons-yes-alt" style="color: green;"></span> ' . __('Configured', 'ai-chatbot-rag');
                            } else {
                                echo '<span class="dashicons dashicons-warning" style="color: orange;"></span> ' . __('Not configured', 'ai-chatbot-rag');
                            }
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
