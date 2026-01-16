<?php
/**
 * Plugin Name: AI Chatbot PIP
 * Plugin URI: https://partnerinpublishing.com/
 * Description: Intelligent customer support chatbot, trained on the website’s own content, using RAG to deliver contextualized, accurate, and real-time responses.
 * Version: 1.1.0
 * Author: Dev Team PIP
 * License: MIT
 * License URI: https://mit-license.org/
 * Requires at least: 6.0
 * Requires PHP: 8.0
 *
 * @package AIChatbotRAG
 */

namespace AIChatbotRAG;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('AI_CHATBOT_RAG_VERSION', '1.0.5');
define('AI_CHATBOT_RAG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AI_CHATBOT_RAG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AI_CHATBOT_RAG_PLUGIN_FILE', __FILE__);
define('AI_CHATBOT_RAG_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class Plugin {

    private static ?Plugin $instance = null;

    private function __construct() {
        $this->loadDependencies();
        $this->initHooks();
    }

    public static function getInstance(): Plugin {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadDependencies(): void {
        // Core
        require_once AI_CHATBOT_RAG_PLUGIN_DIR . 'includes/class-database.php';
        require_once AI_CHATBOT_RAG_PLUGIN_DIR . 'includes/class-activator.php';
        require_once AI_CHATBOT_RAG_PLUGIN_DIR . 'includes/class-deactivator.php';

        // Services
        require_once AI_CHATBOT_RAG_PLUGIN_DIR . 'includes/services/class-content-indexer.php';
        require_once AI_CHATBOT_RAG_PLUGIN_DIR . 'includes/services/class-embeddings-service.php';
        require_once AI_CHATBOT_RAG_PLUGIN_DIR . 'includes/services/class-deepseek-client.php';
        require_once AI_CHATBOT_RAG_PLUGIN_DIR . 'includes/services/class-rag-engine.php';

        // Admin
        require_once AI_CHATBOT_RAG_PLUGIN_DIR . 'admin/class-admin.php';
        require_once AI_CHATBOT_RAG_PLUGIN_DIR . 'admin/class-settings.php';

        // Public
        require_once AI_CHATBOT_RAG_PLUGIN_DIR . 'public/class-chatbot.php';
        require_once AI_CHATBOT_RAG_PLUGIN_DIR . 'public/class-rest-api.php';
    }

    private function initHooks(): void {
        register_activation_hook(__FILE__, [Activator::class, 'activate']);
        register_deactivation_hook(__FILE__, [Deactivator::class, 'deactivate']);

        add_action('plugins_loaded', [$this, 'init']);
    }

    public function init(): void {
        // Load text domain
        load_plugin_textdomain('ai-chatbot-rag', false, dirname(AI_CHATBOT_RAG_PLUGIN_BASENAME) . '/languages');

        // Initialize components
        if (is_admin()) {
            new Admin\Admin();
            new Admin\Settings();
        }

        new PublicFacing\Chatbot();
        new PublicFacing\RestAPI();
    }
}

// Initialize plugin immediately
Plugin::getInstance();
