# AI Chatbot RAG - WordPress Plugin

Professional WordPress plugin that implements a customer support chatbot based on RAG (Retrieval-Augmented Generation) using DeepSeek as the language model.

## Key Features

### ✅ Complete RAG System
- Automatic WordPress content indexing
- Contextual search based on site content
- Responses based ONLY on site information (does not make things up)
- Architecture ready to migrate to external vector databases

### ✅ Intelligent Indexing
- Posts, Pages, Custom Post Types
- WooCommerce Products (if installed)
- ACF Fields (if available)
- Automatic cleanup of HTML, shortcodes, and scripts
- Content splitting into chunks with configurable overlap

### ✅ DeepSeek Integration
- Model: `deepseek-chat`
- Error handling and rate limiting
- Automatic retries with exponential backoff
- Temperature and token control

### ✅ Prompt Engineering
- Configurable prompt system
- Conversation history (last N messages)
- Customizable message when no context available
- Cited sources in responses

### ✅ User Interface
- `[ai_chatbot]` shortcode to insert on any page
- Responsive and modern design
- Typing indicator
- Clickable sources
- Floating widget with customizable position and appearance

### ✅ Administration Panel
- Dashboard with statistics
- Complete configuration
- One-click indexing
- Indexed content visualization

## Requirements

- WordPress 6.0+
- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.2+
- DeepSeek API Key ([get it here](https://platform.deepseek.com/))

## Installation

### Method 1: Manual Upload

1. Download the plugin as ZIP
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Select the ZIP file and click "Install Now"
4. Activate the plugin

### Method 2: FTP

1. Download and unzip the plugin
2. Upload the `ai-chatbot-rag` folder to `/wp-content/plugins/`
3. Activate the plugin from the WordPress admin panel

## Quick Setup

### 1. Configure DeepSeek API

1. Go to **AI Chatbot → Settings**
2. Enter your DeepSeek API Key
3. Configure the model parameters:
   - **Temperature**: 0.3 (recommended for accurate responses)
   - **Max Tokens**: 1000
   - **Model**: deepseek-chat

### 2. Index Content

1. Go to **AI Chatbot → Indexing**
2. Click "Start Indexing"
3. Wait for the process to complete
4. Verify the statistics

### 3. Add Chatbot

Use the shortcode on any page or post:

```
[ai_chatbot]
```

With optional parameters:

```
[ai_chatbot title="How can we help?" height="600px"]
```

## Advanced Configuration

### Indexing Parameters

- **Chunk Size**: Size of each text fragment (recommended: 500 words)
- **Chunk Overlap**: Words that overlap between chunks (recommended: 50)
- **Post Types**: Content types to index (posts, pages, products, etc.)
- **Exclude Categories**: Categories to exclude from indexing

### RAG Parameters

- **Max Context Chunks**: Maximum number of fragments to include in context (recommended: 5)
- **Conversation History**: Previous messages to include (recommended: 5)
- **System Prompt**: System prompt (use `{context}` as placeholder)
- **No Context Message**: Message when no relevant information is available

### Default System Prompt

```
You are a professional and helpful virtual assistant for the website.

STRICT RULES:
1. You can ONLY respond using information provided in the CONTEXT below.
2. If the information is NOT in the context, you must respond: "I'm sorry, I don't have information about that topic in our knowledge base."
3. DO NOT invent, assume, or elaborate on information that is not explicitly in the context.
4. Be clear, concise, and professional.
5. If the user asks something outside the context, admit it honestly.

CONTEXT:
{context}

Respond in a helpful and professional manner based SOLELY on the provided context.
```

## Technical Architecture

### Directory Structure

```
ai-chatbot-rag/
├── ai-chatbot-rag.php          # Main plugin file
├── includes/
│   ├── class-database.php       # Database management
│   ├── class-activator.php      # Plugin activation
│   ├── class-deactivator.php    # Plugin deactivation
│   └── services/
│       ├── class-content-indexer.php     # Content indexing
│       ├── class-embeddings-service.php  # Embeddings service
│       ├── class-deepseek-client.php     # DeepSeek API client
│       └── class-rag-engine.php          # RAG engine
├── admin/
│   ├── class-admin.php          # Admin interface
│   ├── class-settings.php       # Settings configuration
│   └── views/                   # Admin views
├── public/
│   ├── class-chatbot.php        # Widget/Shortcode
│   ├── class-rest-api.php       # REST API
│   └── views/                   # Public views
└── assets/
    ├── css/                     # Styles
    └── js/                      # JavaScript
```

### Database

The plugin creates 3 tables:

1. **wp_ai_chatbot_chunks**: Stores content fragments
   - `id`, `post_id`, `post_type`, `chunk_index`
   - `content`, `content_clean`, `content_hash`
   - `word_count`, `metadata`

2. **wp_ai_chatbot_embeddings**: Stores embeddings (ready for vector DB)
   - `id`, `chunk_id`, `embedding_model`
   - `embedding`, `dimensions`

3. **wp_ai_chatbot_conversations**: Conversation history
   - `id`, `session_id`, `user_id`
   - `role`, `message`, `metadata`

### REST API Endpoints

- `POST /wp-json/ai-chatbot-rag/v1/chat`
  - Send message and receive response
  - Requires: `message`, `session_id`
  - Rate limit: 10 req/min per IP

- `GET /wp-json/ai-chatbot-rag/v1/health`
  - Check system status

- `GET /wp-json/ai-chatbot-rag/v1/stats` (admin only)
  - System statistics

## Contextual Search (MVP)

The MVP version uses keyword-based search with TF-IDF:

1. Extract keywords from user query
2. Search for chunks containing those keywords
3. Calculate relevance score
4. Return top N most relevant chunks

**Future versions**: Migration to vector embeddings with:
- OpenAI Embeddings
- Pinecone / Weaviate / Qdrant
- Cosine similarity search

## Security

### Implemented

✅ Nonces for all AJAX requests
✅ Input sanitization
✅ Permission validation
✅ Rate limiting per IP
✅ SQL prepared statements
✅ Output escaping
✅ HTTPOnly cookies

### Recommendations

- Keep your API Key secure
- Don't share credentials in public repositories
- Monitor API usage
- Limit access to sensitive endpoints

## Customization

### Modify Styles

Edit `assets/css/chatbot.css` or add custom CSS:

```css
.ai-chatbot-container {
    /* Your CSS here */
}
```

### Modify Prompts

From **AI Chatbot → Settings → RAG Settings**, you can customize:
- System Prompt
- No Context Message
- Chatbot Title
- Input Placeholder

### Available Hooks (Coming Soon)

```php
// Filter content before indexing
add_filter('ai_chatbot_rag_before_index', function($content, $post_id) {
    // Modify content
    return $content;
}, 10, 2);

// Modify response before sending
add_filter('ai_chatbot_rag_response', function($response, $query) {
    // Modify response
    return $response;
}, 10, 2);
```

## Troubleshooting

### Chatbot Not Responding

1. Verify that the API Key is configured
2. Check that content is indexed
3. Check browser console for JavaScript errors
4. Verify that rate limit is not blocking

### Indexing Fails

1. Increase PHP memory limit (`memory_limit` in php.ini)
2. Increase execution time (`max_execution_time`)
3. Check WordPress logs
4. Index in batches if you have a lot of content

### Responses Are Not Relevant

1. Adjust chunk size
2. Increase number of chunks in context
3. Improve system prompt
4. Verify that content is correctly indexed

### API Errors

1. Verify that the API Key is valid
2. Check your DeepSeek plan limit
3. Check logs (`error_log`)
4. Adjust timeout if connection is slow

## Roadmap

### v1.1
- [x] Configurable floating widget
- [ ] Export conversations
- [ ] Metrics and analytics
- [ ] User feedback

### v1.2
- [ ] Real vector embeddings
- [ ] Pinecone/Weaviate integration
- [ ] Improved multi-language support
- [ ] A/B testing for prompts

### v2.0
- [ ] Multiple models (OpenAI, Claude, etc.)
- [ ] Custom fine-tuning
- [ ] CRM integrations
- [ ] Public API for developers

## Customization Prompt Example

Example prompt for Partner in Publishing:

```
You are a professional virtual assistant for Partner in Publishing, a publishing services company.

YOUR ROLE:
- Help visitors understand our services and capabilities
- Provide accurate information based solely on the context below
- Guide visitors to contact us for personalized assistance

STRICT RULES:
1. ONLY answer using information from the CONTEXT below
2. DO NOT invent, assume, or add information not in the context
3. If the answer is NOT in the context, politely invite the visitor to contact us
4. Always maintain a professional, helpful, and encouraging tone
5. When appropriate, encourage visitors to reach out for personalized quotes or consultations

CONTEXT:
{context}

CONTACT INVITATION:
When you don't have specific information, or when the visitor's needs require personalized attention, use this response format:
"I'd be happy to help you with that! For personalized assistance with [topic], I invite you to contact our team directly. You can reach us through our contact form:
https://partnerinpublishing.com/#brxe-8292d9

Our team will get back to you promptly to discuss your specific needs."

Remember: Your goal is to be helpful, informative, and guide visitors toward contacting us when needed.
```

📋 **How to implement it:**

1. Go to WordPress Admin → AI Chatbot → Settings
2. Find the "System Prompt Template" field
3. Delete the current content
4. Paste this new prompt
5. Click "Save Changes"

## Contributing

Contributions are welcome! Please:

1. Fork the project
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License

GPL v2 or later

## Support

- Issues: [GitHub Issues](https://github.com/yourname/ai-chatbot-rag/issues)
- Documentation: [Wiki](https://github.com/yourname/ai-chatbot-rag/wiki)
- Email: support@yoursite.com

---

## ✍️ Author

- Developed by **Carlos Garzón**
- Software Engineer, Fullstack Developer

---

## 📄 License

This project is licensed under the MIT License.

---
