# ChaBotRag — AI Support Chatbot for WordPress (RAG)

WordPress plugin that delivers contextual customer support answers using Retrieval-Augmented Generation (RAG), grounded in the site’s own content.

## Why this project matters
- Solves a real business problem: scalable first-line support with better answer relevance.
- Uses practical AI architecture (indexing + retrieval + generation), not just prompt wrappers.
- Built as a production-oriented WordPress plugin with admin controls and observability.

## Key Capabilities
- Automatic indexing of WordPress content (posts/pages/CPT; WooCommerce optional)
- Content chunking + overlap for better retrieval quality
- Response generation constrained to indexed context
- Conversation memory and configurable system prompts
- Source references in answers
- Admin dashboard for settings, indexing, and monitoring

## Tech Stack
- **Platform:** WordPress / PHP 8+
- **AI:** DeepSeek (`deepseek-chat`)
- **Pattern:** RAG (retrieve + generate)
- **UI:** shortcode + floating widget

## Plugin Highlights
- `[ai_chatbot]` shortcode for inline embed
- Responsive chat UI with typing indicator
- Retry/backoff and error handling for API calls
- Configurable model params (temperature/tokens)

## Installation
1. Install plugin (ZIP or FTP) under `/wp-content/plugins/`
2. Activate in WordPress admin
3. Configure API key and model params
4. Run indexing from admin panel
5. Add shortcode to target pages

## Business Impact
- Faster support response times
- Better consistency in customer communication
- Reduced manual support load for repetitive questions

## Author
**Carlos Garzón**
