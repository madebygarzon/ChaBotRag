<?php
/**
 * DeepSeek API Client
 *
 * @package AIChatbotRAG
 */

namespace AIChatbotRAG\Services;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class DeepSeekClient
 * Handles communication with DeepSeek API
 */
class DeepSeekClient {

    private const API_BASE_URL = 'https://api.deepseek.com/v1';
    private const TIMEOUT = 30;
    private const MAX_RETRIES = 3;

    private string $apiKey;
    private string $model;
    private float $temperature;
    private int $maxTokens;

    public function __construct() {
        $this->apiKey = get_option('ai_chatbot_rag_deepseek_api_key', '');
        $this->model = get_option('ai_chatbot_rag_deepseek_model', 'deepseek-chat');
        $this->temperature = (float) get_option('ai_chatbot_rag_temperature', 0.3);
        $this->maxTokens = (int) get_option('ai_chatbot_rag_max_tokens', 1000);
    }

    /**
     * Send chat completion request to DeepSeek
     */
    public function chatCompletion(array $messages): array {
        if (empty($this->apiKey)) {
            throw new \Exception(__('DeepSeek API key is not configured.', 'ai-chatbot-rag'));
        }

        $body = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
            'stream' => false,
        ];

        $response = $this->makeRequest('chat/completions', $body);

        if (isset($response['error'])) {
            throw new \Exception(
                sprintf(
                    __('DeepSeek API error: %s', 'ai-chatbot-rag'),
                    $response['error']['message'] ?? 'Unknown error'
                )
            );
        }

        return $response;
    }

    /**
     * Make HTTP request to DeepSeek API with retry logic
     */
    private function makeRequest(string $endpoint, array $body, int $attempt = 1): array {
        $url = self::API_BASE_URL . '/' . ltrim($endpoint, '/');

        $args = [
            'timeout' => self::TIMEOUT,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($body),
            'method' => 'POST',
        ];

        $response = wp_remote_post($url, $args);

        // Handle HTTP errors
        if (is_wp_error($response)) {
            if ($attempt < self::MAX_RETRIES) {
                // Exponential backoff
                sleep(pow(2, $attempt));
                return $this->makeRequest($endpoint, $body, $attempt + 1);
            }

            throw new \Exception(
                sprintf(
                    __('HTTP request failed: %s', 'ai-chatbot-rag'),
                    $response->get_error_message()
                )
            );
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        $responseBody = wp_remote_retrieve_body($response);

        // Handle rate limiting (429)
        if ($statusCode === 429 && $attempt < self::MAX_RETRIES) {
            $retryAfter = (int) wp_remote_retrieve_header($response, 'retry-after') ?: pow(2, $attempt);
            sleep($retryAfter);
            return $this->makeRequest($endpoint, $body, $attempt + 1);
        }

        // Handle server errors (5xx)
        if ($statusCode >= 500 && $attempt < self::MAX_RETRIES) {
            sleep(pow(2, $attempt));
            return $this->makeRequest($endpoint, $body, $attempt + 1);
        }

        $data = json_decode($responseBody, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception(
                sprintf(
                    __('Invalid JSON response: %s', 'ai-chatbot-rag'),
                    json_last_error_msg()
                )
            );
        }

        // Handle API errors (4xx)
        if ($statusCode >= 400) {
            $errorMessage = $data['error']['message'] ?? 'Unknown API error';
            $errorType = $data['error']['type'] ?? 'unknown';

            throw new \Exception(
                sprintf(
                    __('DeepSeek API error [%s]: %s', 'ai-chatbot-rag'),
                    $errorType,
                    $errorMessage
                )
            );
        }

        return $data;
    }

    /**
     * Validate API key
     */
    public function validateApiKey(): bool {
        try {
            $messages = [
                [
                    'role' => 'user',
                    'content' => 'Test',
                ],
            ];

            $this->chatCompletion($messages);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get model information
     */
    public function getModelInfo(): array {
        return [
            'model' => $this->model,
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
        ];
    }

    /**
     * Estimate token count (rough approximation)
     */
    public function estimateTokens(string $text): int {
        // Rough estimation: ~1 token per 4 characters for English
        // ~1 token per 2-3 characters for other languages
        return (int) ceil(mb_strlen($text, 'UTF-8') / 3);
    }

    /**
     * Truncate text to fit within token limit
     */
    public function truncateToTokenLimit(string $text, int $maxTokens): string {
        $estimatedTokens = $this->estimateTokens($text);

        if ($estimatedTokens <= $maxTokens) {
            return $text;
        }

        // Calculate approximate character limit
        $charLimit = (int) ($maxTokens * 3);
        $truncated = mb_substr($text, 0, $charLimit, 'UTF-8');

        // Try to cut at word boundary
        $lastSpace = mb_strrpos($truncated, ' ', 0, 'UTF-8');
        if ($lastSpace !== false) {
            $truncated = mb_substr($truncated, 0, $lastSpace, 'UTF-8');
        }

        return $truncated . '...';
    }
}
