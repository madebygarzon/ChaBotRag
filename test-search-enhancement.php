<?php
/**
 * Test Search Enhancement
 */

// Include WordPress
$wp_config_path = dirname(__DIR__, 3) . '/wp-config.php';
if (file_exists($wp_config_path)) {
    require_once $wp_config_path);
}

// Test the enhanced search
function test_search_enhancement() {
    $query = "What is Torus platform";
    
    echo "=== Testing Enhanced Search ===\n";
    echo "Query: $query\n\n";
    
    // Test the new search strategies
    $embeddingsService = new \AIChatbotRAG\Services\EmbeddingsService();
    $results = $embeddingsService->searchChunksFullText($query, 3);
    
    echo "Found " . count($results) . " chunks:\n\n";
    
    foreach ($results as $i => $chunk) {
        echo "--- Chunk " . ($i + 1) . " ---\n";
        echo "Content: " . substr($chunk->content_clean, 0, 200) . "...\n";
        
        if (isset($chunk->metadata['title'])) {
            echo "Title: " . $chunk->metadata['title'] . "\n";
        }
        
        echo "Score: " . calculateScore($chunk->content_clean, $query) . "\n";
        echo "\n";
    }
    
    echo "=== Test Complete ===\n";
}

function calculateScore($content, $query) {
    $score = 0;
    $contentLower = strtolower($content);
    $queryLower = strtolower($query);
    
    // Exact match bonus
    if (strpos($contentLower, $queryLower) !== false) {
        $score += 10;
    }
    
    // Q&A format bonus
    if (strpos($contentLower, 'q:') !== false && strpos($contentLower, 'a:') !== false) {
        $score += 5;
    }
    
    // What/How/Why questions
    if (preg_match('/\b(what|how|when|where|why|which|who)\b/i', $query)) {
        if (strpos($contentLower, 'platform') !== false) {
            $score += 8;
        }
    }
    
    return $score;
}

// Run the test
test_search_enhancement();
?>