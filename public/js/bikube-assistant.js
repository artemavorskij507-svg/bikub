/**
 * Bikube Smart Assistant Module
 * Оптимизированный модуль для работы с ассистентом
 */
class BikubeAssistant {
    constructor(config) {
        this.config = {
            apiUrl: config.apiUrl || '/lk/assistant/message',
            csrfToken: config.csrfToken || '',
            storageKey: 'bikube_assistant_messages',
            maxMessages: 50,
            debounceDelay: 300,
            ...config
        };
        
        this.state = {
            open: false,
            input: '',
            loading: false,
            messages: [],
            error: null
        };
        
        this.debounceTimer = null;
        this.init();
    }
    
    init() {
        this.loadMessages();
        this.setupEventListeners();
    }
    
    setupEventListeners() {
        // ESC для закрытия
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.state.open) {
                this.close();
            }
        });
        
        // Автосохранение при изменении сообщений
        this.watchMessages();
    }
    
    watchMessages() {
        // Используем MutationObserver для отслеживания изменений
        const observer = new MutationObserver(() => {
            this.debouncedSave();
        });
        
        // Наблюдаем за изменениями в DOM (если нужно)
        // В реальности лучше использовать Alpine.js watch
    }
    
    debouncedSave() {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => {
            this.saveMessages();
        }, this.config.debounceDelay);
    }
    
    async send(message = null) {
        const text = (message || this.state.input).trim();
        if (!text || this.state.loading) return;
        
        // Добавляем сообщение пользователя
        this.addMessage('user', text);
        this.state.input = '';
        this.state.loading = true;
        this.state.error = null;
        
        this.scrollToBottom();
        
        try {
            const response = await fetch(this.config.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.config.csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ message: text }),
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            const reply = data.reply || data.message || 'Ассистент временно недоступен. Попробуйте позже.';
            
            this.addMessage('assistant', reply);
            this.scrollToBottom();
            
        } catch (error) {
            console.error('Assistant error:', error);
            this.handleError(error);
        } finally {
            this.state.loading = false;
        }
    }
    
    addMessage(role, text) {
        const message = {
            role,
            text,
            timestamp: new Date()
        };
        
        this.state.messages.push(message);
        
        // Ограничиваем количество сообщений
        if (this.state.messages.length > this.config.maxMessages) {
            this.state.messages = this.state.messages.slice(-this.config.maxMessages);
        }
        
        this.debouncedSave();
    }
    
    handleError(error) {
        let errorMessage = '❌ Ошибка запроса к ассистенту. Попробуйте позже.';
        
        if (error.message && error.message.includes('Failed to fetch')) {
            errorMessage = '⚠️ Нет соединения с сервером. Проверьте интернет-соединение.';
        } else if (error.message && error.message.includes('HTTP error')) {
            errorMessage = '⚠️ Ошибка сервера. Попробуйте позже.';
        }
        
        this.addMessage('assistant', errorMessage);
        this.scrollToBottom();
    }
    
    scrollToBottom() {
        // Используется через Alpine.js ref
        this.$nextTick?.(() => {
            const el = document.querySelector('[x-ref="messages"]');
            if (el) {
                el.scrollTop = el.scrollHeight;
            }
        });
    }
    
    clearMessages() {
        if (this.state.messages.length === 0) return;
        
        if (confirm('🗑️ Очистить всю историю чата?')) {
            this.state.messages = [];
            try {
                localStorage.removeItem(this.config.storageKey);
            } catch (e) {
                console.warn('LocalStorage not available');
            }
        }
    }
    
    loadMessages() {
        try {
            const saved = localStorage.getItem(this.config.storageKey);
            if (saved) {
                const parsed = JSON.parse(saved);
                this.state.messages = parsed.slice(-this.config.maxMessages);
            }
        } catch (e) {
            console.warn('Failed to load messages from localStorage');
        }
    }
    
    saveMessages() {
        try {
            if (this.state.messages.length > 0) {
                localStorage.setItem(
                    this.config.storageKey,
                    JSON.stringify(this.state.messages)
                );
            }
        } catch (e) {
            console.warn('Failed to save messages to localStorage');
        }
    }
    
    getSampleQuestions() {
        return [
            '📦 Какие у меня есть активные заказы?',
            '💰 Сколько я заработал сегодня?',
            '📊 Моя статистика',
            '📅 График работы',
        ];
    }
    
    open() {
        this.state.open = true;
    }
    
    close() {
        this.state.open = false;
    }
    
    toggle() {
        this.state.open = !this.state.open;
    }
}

// Экспорт для использования
if (typeof window !== 'undefined') {
    window.BikubeAssistant = BikubeAssistant;
}

