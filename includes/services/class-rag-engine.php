<?php
/**
 * RAG Engine
 *
 * @package AIChatbotRAG
 */

namespace AIChatbotRAG\Services;

use AIChatbotRAG\Database;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class RAGEngine
 * Orchestrates the Retrieval-Augmented Generation process
 */
class RAGEngine {

    private EmbeddingsService $embeddingsService;
    private DeepSeekClient $deepseekClient;
    private int $maxContextChunks;
    private int $conversationHistory;
    private string $systemPrompt;
    private string $noContextMessage;

    public function __construct() {
        $this->embeddingsService = new EmbeddingsService();
        $this->deepseekClient = new DeepSeekClient();
        $this->maxContextChunks = (int) get_option('ai_chatbot_rag_max_context_chunks', 5);
        $this->conversationHistory = (int) get_option('ai_chatbot_rag_conversation_history', 5);
        $this->systemPrompt = get_option('ai_chatbot_rag_system_prompt', '');
        $this->noContextMessage = get_option('ai_chatbot_rag_no_context_message', '');
    }

    /**
     * Process a user message and generate response
     */
    public function chat(string $userMessage, string $sessionId, ?int $userId = null): array {
        try {
            error_log('AI Chatbot RAG: RAG Engine chat started');

            // 1. Retrieve relevant context chunks
            error_log('AI Chatbot RAG: Retrieving context...');
            $relevantChunks = $this->retrieveContext($userMessage);
            error_log('AI Chatbot RAG: Found ' . count($relevantChunks) . ' relevant chunks');

            // 2. Build context string
            error_log('AI Chatbot RAG: Building context string...');
            $context = $this->buildContextString($relevantChunks);

            // 3. Get conversation history
            error_log('AI Chatbot RAG: Getting conversation history...');
            $history = $this->getConversationHistory($sessionId);
            error_log('AI Chatbot RAG: Found ' . count($history) . ' history messages');

            // 4. Build messages for LLM
            error_log('AI Chatbot RAG: Building messages for LLM...');
            $messages = $this->buildMessages($userMessage, $context, $history);

            // 5. Call DeepSeek API (always use the API with System Prompt)
            error_log('AI Chatbot RAG: Calling DeepSeek API...');
            $apiResponse = $this->deepseekClient->chatCompletion($messages);
            error_log('AI Chatbot RAG: DeepSeek API response received');

            // 6. Extract response
            $response = $apiResponse['choices'][0]['message']['content'] ?? '';

            if (empty($response)) {
                throw new \Exception(__('Empty response from AI model.', 'ai-chatbot-rag'));
            }

            error_log('AI Chatbot RAG: Response extracted: ' . substr($response, 0, 100));

            // 7. Extract sources
            $sources = $this->extractSources($relevantChunks);

            // 9. Save conversation
            error_log('AI Chatbot RAG: Saving conversation...');
            $this->saveMessage($sessionId, $userId, 'user', $userMessage);
            $this->saveMessage($sessionId, $userId, 'assistant', $response, [
                'sources' => $sources,
                'chunks_used' => count($relevantChunks),
            ]);
            error_log('AI Chatbot RAG: Conversation saved successfully');

            return [
                'success' => true,
                'response' => $response,
                'sources' => $sources,
                'metadata' => [
                    'chunks_retrieved' => count($relevantChunks),
                    'session_id' => $sessionId,
                ],
            ];
        } catch (\Exception $e) {
            error_log('RAG Engine Error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'response' => __('I apologize, but an error occurred while processing your message. Please try again or contact our team at https://partnerinpublishing.com/#brxe-8292d9', 'ai-chatbot-rag'),
            ];
        }
    }

    /**
     * Retrieve relevant context chunks for the query
     */
    private function retrieveContext(string $query): array {
        // Use embeddings service to find similar chunks
        $chunks = $this->embeddingsService->searchChunksFullText($query, $this->maxContextChunks);

        // Fallback to keyword search if no results
        if (empty($chunks)) {
            $chunks = $this->embeddingsService->searchSimilarChunks($query, $this->maxContextChunks);
        }

        return $chunks;
    }

    /**
     * Build context string from chunks
     */
    private function buildContextString(array $chunks): string {
        if (empty($chunks)) {
            return '';
        }

        $contextParts = [];

        foreach ($chunks as $index => $chunk) {
            $metadata = $chunk->metadata ?? [];
            $title = $metadata['title'] ?? 'Sin título';
            $url = $metadata['url'] ?? '';

            $contextParts[] = sprintf(
                "[Fuente %d: %s]\n%s\n",
                $index + 1,
                $title,
                $chunk->content_clean
            );
        }

        return implode("\n---\n", $contextParts);
    }

    /**
     * Build messages array for LLM
     */
    private function buildMessages(string $userMessage, string $context, array $history): array {
        $messages = [];

        // System prompt with context
        $systemPrompt = $this->systemPrompt;

        // If system prompt is empty, use default
        if (empty($systemPrompt)) {
            $systemPrompt = 'Eres un asistente útil que responde preguntas basándose en el siguiente contexto: {context}';
        }

        $systemPromptWithContext = str_replace('{context}', $context, $systemPrompt);

        error_log('AI Chatbot RAG: System prompt length: ' . strlen($systemPromptWithContext));

        $messages[] = [
            'role' => 'system',
            'content' => $systemPromptWithContext,
        ];

        // Add conversation history
        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg->role,
                'content' => $msg->message,
            ];
        }

        // Add current user message
        $messages[] = [
            'role' => 'user',
            'content' => $userMessage,
        ];

        // Ensure total tokens don't exceed limit
        $messages = $this->limitMessageTokens($messages);

        return $messages;
    }

    /**
     * Limit messages to fit within token budget
     */
    private function limitMessageTokens(array $messages): array {
        $maxTokens = $this->deepseekClient->getModelInfo()['max_tokens'];
        $reserveForResponse = 500; // Reserve tokens for response
        $maxInputTokens = $maxTokens - $reserveForResponse;

        $totalTokens = 0;
        $limitedMessages = [];

        // Always keep system message (first) and last user message
        $systemMessage = array_shift($messages);
        $userMessage = array_pop($messages);

        $totalTokens += $this->deepseekClient->estimateTokens($systemMessage['content']);
        $totalTokens += $this->deepseekClient->estimateTokens($userMessage['content']);

        // Add as much history as possible
        $messages = array_reverse($messages); // Start from most recent
        foreach ($messages as $message) {
            $messageTokens = $this->deepseekClient->estimateTokens($message['content']);

            if ($totalTokens + $messageTokens > $maxInputTokens) {
                break;
            }

            $limitedMessages[] = $message;
            $totalTokens += $messageTokens;
        }

        $limitedMessages = array_reverse($limitedMessages);

        // Reconstruct final array
        array_unshift($limitedMessages, $systemMessage);
        $limitedMessages[] = $userMessage;

        return $limitedMessages;
    }

    /**
     * Get conversation history
     */
    private function getConversationHistory(string $sessionId): array {
        global $wpdb;

        $conversationsTable = Database::getTableName(Database::TABLE_CONVERSATIONS);

        return $wpdb->get_results($wpdb->prepare(
            "SELECT role, message FROM {$conversationsTable}
             WHERE session_id = %s
             ORDER BY created_at DESC
             LIMIT %d",
            $sessionId,
            $this->conversationHistory * 2 // *2 to account for user + assistant pairs
        ));
    }

    /**
     * Save message to conversation history
     */
    private function saveMessage(string $sessionId, ?int $userId, string $role, string $message, array $metadata = []): void {
        global $wpdb;

        $conversationsTable = Database::getTableName(Database::TABLE_CONVERSATIONS);

        $wpdb->insert(
            $conversationsTable,
            [
                'session_id' => $sessionId,
                'user_id' => $userId,
                'role' => $role,
                'message' => $message,
                'metadata' => !empty($metadata) ? wp_json_encode($metadata) : null,
            ],
            ['%s', '%d', '%s', '%s', '%s']
        );
    }

    /**
     * Extract source links from chunks
     */
    private function extractSources(array $chunks): array {
        $sources = [];
        $seenUrls = [];

        foreach ($chunks as $chunk) {
            $metadata = $chunk->metadata ?? [];

            if (!empty($metadata['url']) && !in_array($metadata['url'], $seenUrls)) {
                $sources[] = [
                    'title' => $metadata['title'] ?? 'Sin título',
                    'url' => $metadata['url'],
                    'type' => $metadata['type'] ?? 'post',
                ];

                $seenUrls[] = $metadata['url'];
            }
        }

        return $sources;
    }

    /**
     * Clear old conversation history
     */
    public function clearOldConversations(int $daysOld = 30): int {
        global $wpdb;

        $conversationsTable = Database::getTableName(Database::TABLE_CONVERSATIONS);

        return $wpdb->query($wpdb->prepare(
            "DELETE FROM {$conversationsTable} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $daysOld
        ));
    }

    /**
     * Get session statistics
     */
    public function getSessionStats(string $sessionId): array {
        global $wpdb;

        $conversationsTable = Database::getTableName(Database::TABLE_CONVERSATIONS);

        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) as message_count,
                    MIN(created_at) as started_at,
                    MAX(created_at) as last_message_at
             FROM {$conversationsTable}
             WHERE session_id = %s",
            $sessionId
        ), ARRAY_A);

        return $stats ?? [];
    }
}
