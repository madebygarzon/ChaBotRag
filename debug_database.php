<?php
/**
 * Debug script to check database content
 */

// Define WordPress path
$wp_load_path = __DIR__ . '/../../../../../wp-load.php';

if (file_exists($wp_load_path)) {
    require_once($wp_load_path);
} else {
    echo "WordPress not found at expected path\n";
    exit;
}

global $wpdb;

// Check if table exists
$table_name = $wpdb->prefix . 'ai_chatbot_chunks';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

echo "=== Database Debug ===\n";
echo "Table: $table_name\n";
echo "Exists: " . ($table_exists ? "Yes" : "No") . "\n\n";

if ($table_exists) {
    // Get total chunks
    $total_chunks = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    echo "Total chunks: $total_chunks\n\n";
    
    // Search for FAQ/support related content
    $faq_chunks = $wpdb->get_results("
        SELECT id, post_id, post_type, content_clean, metadata 
        FROM $table_name 
        WHERE content_clean LIKE '%support%' 
           OR content_clean LIKE '%FAQ%' 
           OR content_clean LIKE '%courseware%' 
           OR content_clean LIKE '%REAL CHEM%'
           OR content_clean LIKE '%price%'
           OR content_clean LIKE '%cost%'
           OR content_clean LIKE '%student%'
        ORDER BY id DESC 
        LIMIT 10
    ");
    
    echo "FAQ/Support related chunks found: " . count($faq_chunks) . "\n\n";
    
    foreach ($faq_chunks as $chunk) {
        echo "--- Chunk ID: {$chunk->id} ---\n";
        echo "Post ID: {$chunk->post_id}\n";
        echo "Post Type: {$chunk->post_type}\n";
        echo "Content: " . substr($chunk->content_clean, 0, 200) . "...\n";
        echo "Metadata: " . $chunk->metadata . "\n\n";
    }
    
    // Search for Q: format content
    $qa_chunks = $wpdb->get_results("
        SELECT id, post_id, post_type, content_clean, metadata 
        FROM $table_name 
        WHERE content_clean LIKE '%Q: %'
        ORDER BY id DESC 
        LIMIT 5
    ");
    
    echo "Q&A format chunks found: " . count($qa_chunks) . "\n\n";
    
    foreach ($qa_chunks as $chunk) {
        echo "--- Q&A Chunk ID: {$chunk->id} ---\n";
        echo "Post ID: {$chunk->post_id}\n";
        echo "Post Type: {$chunk->post_type}\n";
        echo "Content: " . substr($chunk->content_clean, 0, 300) . "...\n\n";
    }
    
    // Test search query
    $test_query = "have Courseware price";
    echo "=== Testing Search Query ===\n";
    echo "Query: '$test_query'\n\n";
    
    // Simulate the search logic
    $cleanQuery = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $test_query);
    $cleanQuery = preg_replace('/\s+/', ' ', $cleanQuery);
    $terms = explode(' ', $cleanQuery);
    
    echo "Cleaned query: '$cleanQuery'\n";
    echo "Terms: " . implode(', ', $terms) . "\n\n";
    
    // Build search conditions
    $whereConditions = [];
    $prepareArgs = [];
    
    // Exact phrase match
    if (strlen($test_query) > 3) {
        $whereConditions[] = "content_clean LIKE %s";
        $prepareArgs[] = '%' . $wpdb->esc_like($test_query) . '%';
    }
    
    // Individual terms
    foreach ($terms as $term) {
        if (strlen($term) > 2) {
            $whereConditions[] = "content_clean LIKE %s";
            $prepareArgs[] = '%' . $wpdb->esc_like($term) . '%';
        }
    }
    
    // Question-based matching
    if (preg_match('/\b(what|how|when|where|why|which|who|is|are|do|does|can|will|should)\b/i', $test_query)) {
        $whereConditions[] = "content_clean LIKE %s";
        $prepareArgs[] = '%Q: ' . $wpdb->esc_like($test_query) . '%';
    }
    
    echo "Search conditions:\n";
    foreach ($whereConditions as $i => $condition) {
        echo "  $i: $condition\n";
        if (isset($prepareArgs[$i])) {
            echo "     Arg: '{$prepareArgs[$i]}'\n";
        }
    }
    echo "\n";
    
    // Execute search
    if (!empty($whereConditions)) {
        $whereClause = implode(' OR ', $whereConditions);
        $prepareArgs[] = 5;
        
        $sql = "
            SELECT *,
                   CHAR_LENGTH(content_clean) as length,
                   CASE 
                     WHEN content_clean LIKE '%Q: %' THEN 3
                     WHEN content_clean LIKE '%What is%' THEN 2
                     WHEN content_clean LIKE '%How is%' THEN 2
                     WHEN content_clean LIKE '%Why is%' THEN 2
                     ELSE 1
                   END as priority_score
            FROM $table_name
            WHERE $whereClause
            ORDER BY priority_score DESC, word_count DESC
            LIMIT %d
        ";
        
        $results = $wpdb->get_results($wpdb->prepare($sql, ...$prepareArgs));
        
        echo "Search results: " . count($results) . "\n\n";
        
        foreach ($results as $result) {
            echo "--- Result ID: {$result->id} ---\n";
            echo "Priority Score: {$result->priority_score}\n";
            echo "Content: " . substr($result->content_clean, 0, 200) . "...\n\n";
        }
    }
} else {
    echo "Table does not exist. Content may not be indexed yet.\n";
}

?>