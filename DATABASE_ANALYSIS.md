# Database Analysis Report

## Problem Identified
The user query "have Courseware price?" is not finding the FAQ answer "REAL CHEM is $35 per student" that should be indexed from the /support/ page.

## Key Findings

### 1. Content Indexing Process
- **BricksContentExtractor** (`includes/services/class-bricks-content-extractor.php:127-128`) formats FAQ content as:
  ```
  Q: [question]
  A: [answer]
  ```
- **ContentIndexer** (`includes/services/class-content-indexer.php:308-314`) preserves Q/A format during cleaning
- **EmbeddingsService** (`includes/services/class-embeddings-service.php:144-148`) has special handling for Q/A format

### 2. Search Logic Issues
The search algorithm in `EmbeddingsService::enhancedSearch()` has several problems:

#### Problem A: Query Cleaning
```php
// Line 242-256: Removes special characters and stop words
$cleanQuery = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $query);
```
- Removes "?" which is important for question matching
- "have" might be filtered as stop word

#### Problem B: Question Matching Logic
```php
// Line 145-148: Only matches questions starting with specific words
if (preg_match('/\b(what|how|when|where|why|which|who|is|are|do|does|can|will|should)\b/i', $query)) {
    $whereConditions[] = "content_clean LIKE %s";
    $prepareArgs[] = '%Q: ' . $wpdb->esc_like($query) . '%';
}
```
- "have" is not in the question word list
- Should match "have Courseware price?" against "Q: Is there a cost to students for using this courseware?"

#### Problem C: Semantic Gap
- User query: "have Courseware price?"
- Expected FAQ: "Q: Is there a cost to students for using this courseware? A: REAL CHEM is $35 per student"
- No semantic matching between "have price" and "Is there a cost"

### 3. Database Structure
- **Table**: `wp_ai_chatbot_chunks`
- **Fields**: `content_clean`, `metadata`, `post_type`, `post_id`
- **Content stored**: Q/A format from Bricks accordions
- **Metadata**: includes URL, title, post type

## Recommended Solutions

### 1. Improve Question Word Detection
```php
// Add to question words list in class-embeddings-service.php:145
'have', 'cost', 'price', 'fee', 'charge', 'pay'
```

### 2. Enhanced Semantic Matching
```php
// Add synonym matching for price/cost queries
$priceSynonyms = ['price', 'cost', 'fee', 'pay', 'charge', 'expensive'];
foreach ($priceSynonyms as $synonym) {
    if (stripos($query, $synonym) !== false) {
        $whereConditions[] = "content_clean LIKE %s";
        $prepareArgs[] = '%cost%';
        $whereConditions[] = "content_clean LIKE %s";
        $prepareArgs[] = '%price%';
        break;
    }
}
```

### 3. Courseware-Specific Matching
```php
// Add courseware-specific matching
if (stripos($query, 'courseware') !== false) {
    $whereConditions[] = "content_clean LIKE %s";
    $prepareArgs[] = '%courseware%';
    $whereConditions[] = "content_clean LIKE %s";
    $prepareArgs[] = '%student%';
}
```

### 4. Better Query Cleaning
```php
// Preserve question marks and important punctuation
$query = preg_replace('/[^\p{L}\p{N}\s?.!]/u', ' ', $query);
```

### 5. Fuzzy Matching for Questions
```php
// Add partial question matching
$questionPatterns = [
    '%cost%',
    '%price%',
    '%student%',
    '%courseware%'
];
foreach ($questionPatterns as $pattern) {
    $whereConditions[] = "content_clean LIKE %s";
    $prepareArgs[] = $pattern;
}
```

## Testing Recommendations

1. **Verify Content Indexed**: Check if the FAQ content is actually in the database
2. **Test Search Variations**: Try different query formats:
   - "courseware cost"
   - "student price"
   - "REAL CHEM price"
3. **Check URL Prioritization**: Ensure /support/ page content gets priority when user is on support page

## Implementation Priority

1. **High**: Add "have" to question words list
2. **High**: Add price/cost synonym matching
3. **Medium**: Improve query cleaning to preserve question marks
4. **Low**: Implement fuzzy matching for better semantic search

The main issue is that the search algorithm is too rigid and doesn't account for semantic variations in how users ask about pricing compared to how FAQs are formatted.