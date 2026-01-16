<?php
/**
 * Database Handler
 *
 * @package AIChatbotRAG
 */

namespace AIChatbotRAG;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Database
 */
class Database {

    public const TABLE_CHUNKS = 'ai_chatbot_chunks';
    public const TABLE_EMBEDDINGS = 'ai_chatbot_embeddings';
    public const TABLE_CONVERSATIONS = 'ai_chatbot_conversations';

    /**
     * Get full table name with WordPress prefix
     */
    public static function getTableName(string $table): string {
        global $wpdb;
        return $wpdb->prefix . $table;
    }

    /**
     * Create all required tables
     */
    public static function createTables(): void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = [];

        // Chunks table - stores content pieces
        $chunks_table = self::getTableName(self::TABLE_CHUNKS);
        $sql[] = "CREATE TABLE IF NOT EXISTS {$chunks_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id bigint(20) UNSIGNED NOT NULL,
            post_type varchar(20) NOT NULL DEFAULT 'post',
            chunk_index int(11) NOT NULL DEFAULT 0,
            content_hash varchar(64) NOT NULL,
            content text NOT NULL,
            content_clean text NOT NULL,
            word_count int(11) NOT NULL DEFAULT 0,
            metadata longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY post_type (post_type),
            KEY content_hash (content_hash)
        ) {$charset_collate};";

        // Embeddings table - stores vector embeddings (prepared for external vector DB)
        $embeddings_table = self::getTableName(self::TABLE_EMBEDDINGS);
        $sql[] = "CREATE TABLE IF NOT EXISTS {$embeddings_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            chunk_id bigint(20) UNSIGNED NOT NULL,
            embedding_model varchar(100) NOT NULL DEFAULT 'text-embedding-3-small',
            embedding longtext NOT NULL,
            dimensions int(11) NOT NULL DEFAULT 1536,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY chunk_model (chunk_id, embedding_model),
            KEY chunk_id (chunk_id)
        ) {$charset_collate};";

        // Conversations table - stores chat history
        $conversations_table = self::getTableName(self::TABLE_CONVERSATIONS);
        $sql[] = "CREATE TABLE IF NOT EXISTS {$conversations_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id varchar(64) NOT NULL,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            role varchar(20) NOT NULL,
            message text NOT NULL,
            metadata longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY session_id (session_id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        foreach ($sql as $query) {
            dbDelta($query);
        }
    }

    /**
     * Drop all tables
     */
    public static function dropTables(): void {
        global $wpdb;

        $tables = [
            self::getTableName(self::TABLE_CHUNKS),
            self::getTableName(self::TABLE_EMBEDDINGS),
            self::getTableName(self::TABLE_CONVERSATIONS),
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
    }

    /**
     * Clear all data from tables
     */
    public static function clearAllData(): void {
        global $wpdb;

        $tables = [
            self::getTableName(self::TABLE_CHUNKS),
            self::getTableName(self::TABLE_EMBEDDINGS),
            self::getTableName(self::TABLE_CONVERSATIONS),
        ];

        foreach ($tables as $table) {
            $wpdb->query("TRUNCATE TABLE {$table}");
        }
    }
}
