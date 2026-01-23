#!/bin/bash

# Create a simple test to check the database content
echo "=== Database Content Analysis ==="

# Check if we can access WordPress database
if [ -f "./wp-config.php" ]; then
    echo "WordPress config found"
    # Extract database credentials
    DB_NAME=$(grep "DB_NAME" wp-config.php | cut -d "'" -f 4)
    DB_USER=$(grep "DB_USER" wp-config.php | cut -d "'" -f 4)
    DB_PASSWORD=$(grep "DB_PASSWORD" wp-config.php | cut -d "'" -f 4)
    DB_HOST=$(grep "DB_HOST" wp-config.php | cut -d "'" -f 4)
    
    echo "Database: $DB_NAME"
    echo "Host: $DB_HOST"
    echo "User: $DB_USER"
    
    # Try to connect and check tables
    echo ""
    echo "=== Checking Tables ==="
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" -e "SHOW TABLES LIKE '%ai_chatbot%';" 2>/dev/null || echo "MySQL connection failed"
    
    echo ""
    echo "=== Checking Chunks Table ==="
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" -e "SELECT COUNT(*) as total_chunks FROM wp_ai_chatbot_chunks;" 2>/dev/null || echo "Cannot query chunks table"
    
    echo ""
    echo "=== Searching for FAQ/Support Content ==="
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" -e "
    SELECT id, post_id, post_type, LEFT(content_clean, 100) as content_preview 
    FROM wp_ai_chatbot_chunks 
    WHERE content_clean LIKE '%support%' 
       OR content_clean LIKE '%FAQ%' 
       OR content_clean LIKE '%courseware%' 
       OR content_clean LIKE '%REAL CHEM%'
       OR content_clean LIKE '%price%'
       OR content_clean LIKE '%cost%'
       OR content_clean LIKE '%student%'
    ORDER BY id DESC 
    LIMIT 5;
    " 2>/dev/null || echo "Cannot search for FAQ content"
    
    echo ""
    echo "=== Checking Q&A Format ==="
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" -e "
    SELECT id, post_id, post_type, LEFT(content_clean, 150) as content_preview 
    FROM wp_ai_chatbot_chunks 
    WHERE content_clean LIKE '%Q: %'
    ORDER BY id DESC 
    LIMIT 3;
    " 2>/dev/null || echo "Cannot check Q&A format"
    
else
    echo "WordPress config not found in current directory"
    echo "Looking for WordPress installation..."
    
    # Search for wp-config.php in parent directories
    for i in {1..5}; do
        if [ -f "../$(printf '../%.0s' {1..$i})wp-config.php" ]; then
            echo "Found WordPress config at: ../$(printf '../%.0s' {1..$i})"
            break
        fi
    done
fi

echo ""
echo "=== Analysis Complete ==="