/**
 * Chatbot Frontend JavaScript
 */

(function() {
    'use strict';

    class AIChatbotInstance {
        constructor(options = {}) {
            this.root = options.root || null;
            this.form = options.form || null;
            this.input = options.input || null;
            this.sendBtn = options.sendBtn || null;
            this.messagesContainer = options.messagesContainer || null;
            this.floatingBtn = options.floatingBtn || null;
            this.floatingWindow = options.floatingWindow || null;
            this.closeBtn = options.closeBtn || null;
            this.mode = options.mode || 'embedded';

            if (!this.form || !this.input || !this.sendBtn || !this.messagesContainer) {
                return;
            }

            this.isProcessing = false;
            this.isOpen = false;
            this.greetingShown = false;

            this.init();
        }

        init() {
            this.applyThemeVariables();

            this.form.addEventListener('submit', (e) => this.handleSubmit(e));
            this.input.addEventListener('keydown', (e) => this.handleKeydown(e));
            this.input.addEventListener('input', () => this.autoResize());

            if (this.mode === 'floating') {
                if (this.floatingBtn) {
                    this.floatingBtn.addEventListener('click', () => this.toggleChat());
                }

                if (this.closeBtn) {
                    this.closeBtn.addEventListener('click', () => this.toggleChat());
                }
            } else {
                this.showGreeting();
            }
        }

        applyThemeVariables() {
            if (typeof aiChatbotConfig === 'undefined') {
                return;
            }

            if (aiChatbotConfig.primaryColor) {
                document.documentElement.style.setProperty('--chatbot-primary-color', aiChatbotConfig.primaryColor);

                const hoverColor = this.adjustColorBrightness(aiChatbotConfig.primaryColor, -20);
                document.documentElement.style.setProperty('--chatbot-primary-hover', hoverColor);
            }

            if (aiChatbotConfig.botAvatarSize) {
                document.documentElement.style.setProperty('--chatbot-bot-icon-size', aiChatbotConfig.botAvatarSize + 'px');
            }
        }

        toggleChat() {
            if (!this.floatingWindow || !this.floatingBtn) {
                return;
            }

            this.isOpen = !this.isOpen;

            if (this.isOpen) {
                this.floatingWindow.style.display = 'block';
                this.floatingBtn.classList.add('active');

                this.showGreeting();
                this.input.focus();
            } else {
                this.floatingWindow.style.display = 'none';
                this.floatingBtn.classList.remove('active');
            }
        }

        adjustColorBrightness(color, amount) {
            let hex = color.replace('#', '');
            let r = parseInt(hex.substring(0, 2), 16);
            let g = parseInt(hex.substring(2, 4), 16);
            let b = parseInt(hex.substring(4, 6), 16);

            r = Math.max(0, Math.min(255, r + amount));
            g = Math.max(0, Math.min(255, g + amount));
            b = Math.max(0, Math.min(255, b + amount));

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

            this.removeQuickActions();
            this.addMessage(message, 'user');

            this.input.value = '';
            this.input.style.height = 'auto';

            this.setProcessing(true);

            const typingId = this.showTyping();

            try {
                const response = await this.sendMessage(message);

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

        hasRenderableMessages() {
            return this.messagesContainer.querySelector('.ai-chatbot-message') !== null;
        }

        showGreeting() {
            if (this.greetingShown) {
                return;
            }

            if (typeof aiChatbotConfig === 'undefined' || !aiChatbotConfig.greeting) {
                return;
            }

            const greeting = String(aiChatbotConfig.greeting).trim();
            if (!greeting) {
                return;
            }

            if (!this.hasRenderableMessages()) {
                this.addMessage(greeting, 'assistant');
            }

            this.showQuickActions();
            this.greetingShown = true;
        }

        getQuickActions() {
            if (typeof aiChatbotConfig === 'undefined' || !aiChatbotConfig.quickActions) {
                return [];
            }

            const rawQuickActions = aiChatbotConfig.quickActions;
            const quickActions = Array.isArray(rawQuickActions) ? rawQuickActions : [rawQuickActions];

            return quickActions
                .map(action => String(action).trim())
                .filter(action => action !== '');
        }

        removeQuickActions() {
            const quickActionsDiv = this.messagesContainer.querySelector('.ai-chatbot-quick-actions');
            if (quickActionsDiv) {
                quickActionsDiv.remove();
            }
        }

        showQuickActions() {
            this.removeQuickActions();

            const quickActions = this.getQuickActions();
            if (quickActions.length === 0) {
                return;
            }

            const quickActionsDiv = document.createElement('div');
            quickActionsDiv.className = 'ai-chatbot-quick-actions';

            quickActions.forEach(action => {
                const button = document.createElement('button');
                button.className = 'ai-chatbot-quick-action-btn';
                button.type = 'button';
                button.textContent = action;
                button.addEventListener('click', () => this.handleQuickAction(action));
                quickActionsDiv.appendChild(button);
            });

            this.messagesContainer.appendChild(quickActionsDiv);
            this.scrollToBottom();
        }

        handleQuickAction(action) {
            this.removeQuickActions();
            this.input.value = action;
            this.form.dispatchEvent(new Event('submit'));
        }
    }

    function getElementsFromContainer(container) {
        if (!container) {
            return null;
        }

        const form = container.querySelector('.ai-chatbot-form');
        const input = container.querySelector('.ai-chatbot-input');
        const sendBtn = container.querySelector('.ai-chatbot-send-btn');
        const messagesContainer = container.querySelector('.ai-chatbot-messages');

        if (!form || !input || !sendBtn || !messagesContainer) {
            return null;
        }

        return { form, input, sendBtn, messagesContainer };
    }

    function initFloatingChatbot() {
        const floatingWindow = document.getElementById('ai-chatbot-floating-window');
        const floatingBtn = document.getElementById('ai-chatbot-floating-btn');

        if (!floatingWindow || !floatingBtn) {
            return;
        }

        const floatingContainer = floatingWindow.querySelector('.ai-chatbot-container');
        const floatingElements = getElementsFromContainer(floatingContainer);
        const closeBtn = floatingWindow.querySelector('#ai-chatbot-close-btn');

        if (!floatingElements) {
            return;
        }

        new AIChatbotInstance({
            ...floatingElements,
            root: floatingContainer,
            floatingBtn,
            floatingWindow,
            closeBtn,
            mode: 'floating'
        });
    }

    function initEmbeddedChatbots() {
        const containers = document.querySelectorAll('.ai-chatbot-container:not(.ai-chatbot-floating)');

        containers.forEach(container => {
            const elements = getElementsFromContainer(container);
            if (!elements) {
                return;
            }

            new AIChatbotInstance({
                ...elements,
                root: container,
                mode: 'embedded'
            });
        });
    }

    function init() {
        initFloatingChatbot();
        initEmbeddedChatbots();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
