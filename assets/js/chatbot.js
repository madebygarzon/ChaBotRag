/**
 * Chatbot Frontend JavaScript
 */

(function() {
    'use strict';

    class AIChatbot {
        constructor() {
            this.form = document.getElementById('ai-chatbot-form');
            this.input = document.getElementById('ai-chatbot-input');
            this.sendBtn = document.getElementById('ai-chatbot-send-btn');
            this.messagesContainer = document.getElementById('ai-chatbot-messages');
            this.floatingBtn = document.getElementById('ai-chatbot-floating-btn');
            this.floatingWindow = document.getElementById('ai-chatbot-floating-window');
            this.closeBtn = document.getElementById('ai-chatbot-close-btn');

            // Initialize even if form not found (floating button might exist without form initially)
            this.isProcessing = false;
            this.isOpen = false;
            this.init();
        }

        init() {
            // Apply custom color
            if (typeof aiChatbotConfig !== 'undefined' && aiChatbotConfig.primaryColor) {
                document.documentElement.style.setProperty('--chatbot-primary-color', aiChatbotConfig.primaryColor);

                // Calculate darker hover color
                const hoverColor = this.adjustColorBrightness(aiChatbotConfig.primaryColor, -20);
                document.documentElement.style.setProperty('--chatbot-primary-hover', hoverColor);
            }

            // Apply custom bot avatar size
            if (typeof aiChatbotConfig !== 'undefined' && aiChatbotConfig.botAvatarSize) {
                document.documentElement.style.setProperty('--chatbot-bot-icon-size', aiChatbotConfig.botAvatarSize + 'px');
            }

            // Form events (only if form exists)
            if (this.form && this.input) {
                this.form.addEventListener('submit', (e) => this.handleSubmit(e));
                this.input.addEventListener('keydown', (e) => this.handleKeydown(e));
                this.input.addEventListener('input', () => this.autoResize());
            }

            // Floating widget events
            if (this.floatingBtn) {
                this.floatingBtn.addEventListener('click', () => this.toggleChat());
            }

            if (this.closeBtn) {
                this.closeBtn.addEventListener('click', () => this.toggleChat());
            }

            // Greeting shown flag
            this.greetingShown = false;
        }

        toggleChat() {
            this.isOpen = !this.isOpen;

            if (this.isOpen) {
                this.floatingWindow.style.display = 'block';
                this.floatingBtn.classList.add('active');
                
                // Show greeting message on first open
                this.showGreeting();
                
                this.input.focus();
            } else {
                this.floatingWindow.style.display = 'none';
                this.floatingBtn.classList.remove('active');
            }
        }

        adjustColorBrightness(color, amount) {
            // Convert hex to RGB
            let hex = color.replace('#', '');
            let r = parseInt(hex.substring(0, 2), 16);
            let g = parseInt(hex.substring(2, 4), 16);
            let b = parseInt(hex.substring(4, 6), 16);

            // Adjust brightness
            r = Math.max(0, Math.min(255, r + amount));
            g = Math.max(0, Math.min(255, g + amount));
            b = Math.max(0, Math.min(255, b + amount));

            // Convert back to hex
            return '#' + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
        }

        handleKeydown(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.form.dispatchEvent(new Event('submit'));
            }
        }

        autoResize() {
            this.input.style.height = 'auto';
            this.input.style.height = Math.min(this.input.scrollHeight, 100) + 'px';
        }

        async handleSubmit(e) {
            e.preventDefault();

            const message = this.input.value.trim();
            if (!message || this.isProcessing) {
                return;
            }

            // Add user message to UI
            this.addMessage(message, 'user');

            // Clear input
            this.input.value = '';
            this.input.style.height = 'auto';

            // Disable input
            this.setProcessing(true);

            // Show typing indicator
            const typingId = this.showTyping();

            try {
                const response = await this.sendMessage(message);

                // Remove typing indicator
                this.removeTyping(typingId);

                if (response.success) {
                    this.addMessage(response.response, 'assistant', response.sources);
                } else {
                    this.addMessage(response.response || aiChatbotConfig.i18n.error, 'assistant', [], true);
                }
            } catch (error) {
                console.error('Chat error:', error);
                this.removeTyping(typingId);
                this.addMessage(aiChatbotConfig.i18n.error, 'assistant', [], true);
            } finally {
                this.setProcessing(false);
            }
        }

        async sendMessage(message) {
            const response = await fetch(aiChatbotConfig.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': aiChatbotConfig.nonce
                },
                body: JSON.stringify({
                    message: message,
                    session_id: aiChatbotConfig.sessionId,
                    current_page_url: aiChatbotConfig.currentPageUrl
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        }

        addMessage(content, role, sources = [], isError = false) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `ai-chatbot-message ai-chatbot-message-${role}`;

            const contentDiv = document.createElement('div');
            contentDiv.className = 'ai-chatbot-message-content';
            contentDiv.textContent = content;

            if (isError) {
                contentDiv.classList.add('ai-chatbot-error');
            }

            messageDiv.appendChild(contentDiv);

            // Add sources if available
            if (sources && sources.length > 0) {
                const sourcesDiv = document.createElement('div');
                sourcesDiv.className = 'ai-chatbot-message-sources';

                const sourcesTitle = document.createElement('strong');
                sourcesTitle.textContent = aiChatbotConfig.i18n.sources;
                sourcesDiv.appendChild(sourcesTitle);

                const sourcesList = document.createElement('ul');
                sources.forEach(source => {
                    const li = document.createElement('li');
                    const a = document.createElement('a');
                    a.href = source.url;
                    a.textContent = source.title;
                    a.target = '_blank';
                    a.rel = 'noopener noreferrer';
                    li.appendChild(a);
                    sourcesList.appendChild(li);
                });

                sourcesDiv.appendChild(sourcesList);
                contentDiv.appendChild(sourcesDiv);
            }

            this.messagesContainer.appendChild(messageDiv);
            this.scrollToBottom();
        }

        showTyping() {
            const typingDiv = document.createElement('div');
            typingDiv.className = 'ai-chatbot-message ai-chatbot-message-assistant';
            typingDiv.id = 'ai-chatbot-typing-' + Date.now();

            const contentDiv = document.createElement('div');
            contentDiv.className = 'ai-chatbot-message-content';

            const typingIndicator = document.createElement('div');
            typingIndicator.className = 'ai-chatbot-typing';
            typingIndicator.innerHTML = '<span></span><span></span><span></span>';

            contentDiv.appendChild(typingIndicator);
            typingDiv.appendChild(contentDiv);
            this.messagesContainer.appendChild(typingDiv);
            this.scrollToBottom();

            return typingDiv.id;
        }

        removeTyping(typingId) {
            const typingDiv = document.getElementById(typingId);
            if (typingDiv) {
                typingDiv.remove();
            }
        }

        setProcessing(isProcessing) {
            this.isProcessing = isProcessing;
            this.input.disabled = isProcessing;
            this.sendBtn.disabled = isProcessing;

            if (isProcessing) {
                this.sendBtn.style.opacity = '0.5';
            } else {
                this.sendBtn.style.opacity = '1';
                this.input.focus();
            }
        }

        scrollToBottom() {
            this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
        }

        showGreeting() {
            // Check if greeting has already been shown in this session
            if (this.greetingShown) {
                return;
            }

            // Check if greeting message is configured
            if (typeof aiChatbotConfig !== 'undefined' && aiChatbotConfig.greeting) {
                const greeting = aiChatbotConfig.greeting.trim();
                if (greeting && this.messagesContainer.children.length === 0) {
                    this.addMessage(greeting, 'assistant');
                    
                    // Show quick actions after greeting
                    this.showQuickActions();
                    
                    this.greetingShown = true;
                }
            }
        }

        showQuickActions() {
            if (typeof aiChatbotConfig === 'undefined' || !aiChatbotConfig.quickActions) {
                return;
            }

            const quickActions = aiChatbotConfig.quickActions.filter(action => 
                action && action.trim() !== ''
            );

            if (quickActions.length === 0) {
                return;
            }

            // Create quick actions container
            const quickActionsDiv = document.createElement('div');
            quickActionsDiv.className = 'ai-chatbot-quick-actions';

            // Create quick action buttons
            quickActions.forEach(action => {
                const button = document.createElement('button');
                button.className = 'ai-chatbot-quick-action-btn';
                button.textContent = action.trim();
                button.addEventListener('click', () => this.handleQuickAction(action.trim()));
                quickActionsDiv.appendChild(button);
            });

            this.messagesContainer.appendChild(quickActionsDiv);
            this.scrollToBottom();
        }

        handleQuickAction(action) {
            // Remove quick actions when user clicks one
            const quickActionsDiv = this.messagesContainer.querySelector('.ai-chatbot-quick-actions');
            if (quickActionsDiv) {
                quickActionsDiv.remove();
            }

            // Submit the question
            this.input.value = action;
            this.form.dispatchEvent(new Event('submit'));
        }
    }

    // Initialize chatbot when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => new AIChatbot());
    } else {
        new AIChatbot();
    }

})();
