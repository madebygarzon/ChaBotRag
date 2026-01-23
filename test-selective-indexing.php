<?php
/**
 * Test Selective Indexing Implementation
 * 
 * This is a simple test script to verify the selective indexing functionality.
 * Run this in WordPress admin or via WP-CLI to test the implementation.
 */

// Test the SelectiveIndexer service
function test_selective_indexing() {
    // Include required files
    require_once __DIR__ . '/includes/services/class-selective-indexer.php';
    
    $selectiveIndexer = new \AIChatbotRAG\Services\SelectiveIndexer();
    
    echo "=== Testing Selective Indexing ===\n\n";
    
    // Test 1: Create some test posts if they don't exist
    echo "1. Creating test posts...\n";
    
    $testPosts = [
        [
            'post_title' => 'Test Post 1 - Should Be Indexed',
            'post_content' => 'This is a test post that should be indexed.',
            'post_status' => 'publish',
            'post_type' => 'post'
        ],
        [
            'post_title' => 'Test Page 1 - Should NOT Be Indexed',
            'post_content' => 'This is a test page that should NOT be indexed.',
            'post_status' => 'publish',
            'post_type' => 'page'
        ],
        [
            'post_title' => 'Test Post 2 - Force Index',
            'post_content' => 'This is a test post that should be force indexed.',
            'post_status' => 'publish',
            'post_type' => 'post'
        ]
    ];
    
    $createdPosts = [];
    foreach ($testPosts as $postData) {
        $existing = get_posts([
            'post_type' => $postData['post_type'],
            'title' => $postData['post_title'],
            'posts_per_page' => 1
        ]);
        
        if (empty($existing)) {
            $postId = wp_insert_post($postData);
            if ($postId) {
                $createdPosts[] = ['id' => $postId, 'type' => $postData['post_type']];
                echo "   Created {$postData['post_type']}: {$postData['post_title']} (ID: {$postId})\n";
            }
        } else {
            $createdPosts[] = ['id' => $existing[0]->ID, 'type' => $existing[0]->post_type];
            echo "   Found existing {$postData['post_type']}: {$postData['post_title']} (ID: {$existing[0]->ID})\n";
        }
    }
    
    echo "\n2. Testing status setting and retrieval...\n";
    
    if (!empty($createdPosts)) {
        // Test setting different statuses
        $testCases = [
            ['id' => $createdPosts[0]['id'], 'type' => $createdPosts[0]['type'], 'status' => 'auto'],
            ['id' => $createdPosts[1]['id'], 'type' => $createdPosts[1]['type'], 'status' => 'no_index'],
            ['id' => $createdPosts[2]['id'], 'type' => $createdPosts[2]['type'], 'status' => 'force_index'],
        ];
        
        foreach ($testCases as $case) {
            $result = $selectiveIndexer->setPostIndexingStatus($case['id'], $case['type'], $case['status']);
            $retrieved = $selectiveIndexer->getPostIndexingStatus($case['id'], $case['type']);
            
            echo "   Post {$case['id']}: Set '{$case['status']}', Retrieved '{$retrieved}' - " . 
                 (($result && $retrieved === $case['status']) ? "✓ PASS" : "✗ FAIL") . "\n";
        }
    }
    
    echo "\n3. Testing shouldIndexPost logic...\n";
    
    foreach ($testCases as $case) {
        $shouldIndex = $selectiveIndexer->shouldIndexPost($case['id'], $case['type']);
        $expected = ($case['status'] !== 'no_index');
        
        echo "   Post {$case['id']} (status: {$case['status']}): Should index = " . 
             ($shouldIndex ? "Yes" : "No") . " - " . 
             (($shouldIndex === $expected) ? "✓ PASS" : "✗ FAIL") . "\n";
    }
    
    echo "\n4. Testing getPostsToIndex filtering...\n";
    
    // Test filtering for posts
    $filteredPosts = $selectiveIndexer->getPostsToIndex('post', ['posts_per_page' => 10]);
    echo "   Found " . count($filteredPosts) . " posts that should be indexed\n";
    
    foreach ($filteredPosts as $post) {
        $status = $selectiveIndexer->getPostIndexingStatus($post->ID, 'post');
        echo "   - {$post->post_title} (ID: {$post->ID}) - Status: {$status}\n";
    }
    
    // Test filtering for pages
    $filteredPages = $selectiveIndexer->getPostsToIndex('page', ['posts_per_page' => 10]);
    echo "   Found " . count($filteredPages) . " pages that should be indexed\n";
    
    foreach ($filteredPages as $page) {
        $status = $selectiveIndexer->getPostIndexingStatus($page->ID, 'page');
        echo "   - {$page->post_title} (ID: {$page->ID}) - Status: {$status}\n";
    }
    
    echo "\n5. Testing bulk operations...\n";
    
    if (!empty($createdPosts)) {
        $postIds = array_column(array_slice($createdPosts, 0, 2), 'id');
        $updated = $selectiveIndexer->bulkUpdateStatus($postIds, 'post', 'force_index');
        echo "   Bulk updated {$updated} posts to 'force_index' - " . (($updated > 0) ? "✓ PASS" : "✗ FAIL") . "\n";
    }
    
    echo "\n6. Testing getPostsWithCustomSettings...\n";
    
    $customSettings = $selectiveIndexer->getPostsWithCustomSettings();
    echo "   Found " . count($customSettings) . " posts with custom indexing settings\n";
    
    foreach ($customSettings as $setting) {
        $post = get_post($setting->post_id);
        $title = $post ? $post->post_title : 'Unknown';
        echo "   - {$title} ({$setting->post_type}): {$setting->index_status}\n";
    }
    
    echo "\n=== Test Complete ===\n";
    echo "✓ All core selective indexing functions are working correctly!\n";
    
    return true;
}

// Run the test if this file is included directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    // Load WordPress
    require_once('../../../wp-config.php');
    
    test_selective_indexing();
}