{{-- Bikube Smart Assistant - Оптимизированный модуль --}}
<div
    x-data="bikubeAssistant({
        apiUrl: '{{ route('lk.assistant.message') }}',
        csrfToken: '{{ csrf_token() }}'
    })"
    x-init="init()"
    class="fixed bottom-4 right-4 md:bottom-6 md:right-6 z-50"
    @keydown.escape="open = false"
>
    {{-- Chat Window --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95 translate-y-10"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-10"
        x-cloak
        class="mb-4 w-[22rem] sm:w-[28rem] bg-white rounded-3xl shadow-[0_25px_60px_rgb(0,0,0,0.25)] border-2 border-amber-100 flex flex-col overflow-hidden"
        style="max-height: 650px;"
    >
        {{-- Header --}}
        <div class="px-6 py-5 bg-gradient-to-br from-amber-500 via-orange-500 to-amber-600 flex items-center justify-between shadow-xl relative overflow-hidden">
            {{-- Decorative background --}}
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 right-0 w-32 h-32 bg-white rounded-full -mr-16 -mt-16"></div>
                <div class="absolute bottom-0 left-0 w-24 h-24 bg-white rounded-full -ml-12 -mb-12"></div>
            </div>
            
            <div class="flex items-center space-x-4 relative z-10">
                <div class="w-12 h-12 rounded-2xl bg-white/25 backdrop-blur-sm flex items-center justify-center shadow-lg border-2 border-white/30">
                    <svg class="w-7 h-7 text-white drop-shadow-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                    </svg>
                </div>
                <div>
                    <div class="text-base font-black text-white tracking-tight leading-tight">Bikube Smart</div>
                    <div class="text-xs text-amber-100 font-semibold mt-0.5">Онлайн помощник</div>
                </div>
            </div>
            <div class="flex items-center space-x-2 relative z-10">
                <button
                    class="text-white/80 hover:text-white p-2.5 rounded-xl hover:bg-white/20 transition-all duration-200 active:scale-95"
                    @click="clearMessages()"
                    title="Очистить чат"
                    x-show="messages.length > 0"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                    </svg>
                </button>
                <button
                    class="text-white/80 hover:text-white p-2.5 rounded-xl hover:bg-white/20 transition-all duration-200 active:scale-95"
                    @click="open = false"
                    title="Закрыть (ESC)"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Messages Area --}}
        <div class="p-5 space-y-4 max-h-[450px] overflow-y-auto text-sm bg-white custom-scrollbar" x-ref="messages">
            {{-- Empty State --}}
            <template x-if="messages.length === 0 && !loading">
                <div class="text-center py-10 px-4">
                    <div class="w-24 h-24 rounded-3xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center mx-auto mb-5 shadow-xl border-4 border-white">
                        <svg class="w-12 h-12 text-white drop-shadow-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                        </svg>
                    </div>
                    <div class="text-black font-black text-xl mb-2" style="color: #000000 !important;">Привет! Я Bikube Smart</div>
                    <p class="text-sm font-semibold mb-7 leading-relaxed max-w-xs mx-auto" style="color: #000000 !important;">
                        Я помогу вам с информацией о заказах, графике, заработках и статистике. Спрашивайте!
                    </p>
                    <div class="space-y-3">
                        <template x-for="question in getSampleQuestions()" :key="question">
                            <button
                                @click="sendSample(question)"
                                class="w-full text-left text-sm px-5 py-3.5 bg-white border-2 border-slate-200 rounded-2xl hover:border-amber-300 hover:bg-amber-50 transition-all font-bold shadow-md hover:shadow-lg hover:scale-[1.02] active:scale-[0.98]"
                                style="color: #000000 !important;"
                                x-text="question"
                            ></button>
                        </template>
                    </div>
                </div>
            </template>

            {{-- Messages --}}
            <template x-for="(msg, index) in messages" :key="index">
                <div class="flex" :class="msg.role === 'user' ? 'justify-end' : 'justify-start'">
                    <div class="flex items-end space-x-2 max-w-[85%]" :class="msg.role === 'user' ? 'flex-row-reverse space-x-reverse' : ''">
                        {{-- Avatar --}}
                        <div x-show="msg.role === 'assistant'" class="w-8 h-8 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center flex-shrink-0 shadow-md border border-white">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                            </svg>
                        </div>

                        {{-- Message Bubble --}}
                        <div
                            class="px-5 py-3.5 rounded-2xl shadow-md text-sm font-semibold leading-relaxed"
                            :class="msg.role === 'user'
                                ? 'bg-gradient-to-br from-slate-800 to-slate-900 text-white rounded-br-sm'
                                : 'bg-white rounded-bl-sm border-2 border-amber-100 shadow-sm'"
                            :style="msg.role === 'assistant' ? 'color: #000000 !important;' : ''"
                        >
                            <div x-html="formatMessage(msg.text)" class="break-words" :style="msg.role === 'assistant' ? 'color: #000000 !important;' : ''"></div>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Loading Indicator --}}
            <template x-if="loading">
                <div class="flex justify-start">
                    <div class="flex items-end space-x-2">
                        <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center flex-shrink-0 shadow-md border border-white">
                            <svg class="w-4 h-4 text-white animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                        </div>
                        <div class="px-4 py-3 rounded-2xl rounded-bl-none bg-white text-slate-800 border-2 border-amber-100 shadow-sm">
                            <div class="flex space-x-1.5">
                                <span class="w-1.5 h-1.5 bg-amber-500 rounded-full animate-bounce" style="animation-delay: 0s;"></span>
                                <span class="w-1.5 h-1.5 bg-amber-500 rounded-full animate-bounce" style="animation-delay: 0.2s;"></span>
                                <span class="w-1.5 h-1.5 bg-amber-500 rounded-full animate-bounce" style="animation-delay: 0.4s;"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Input Form --}}
        <form class="p-5 bg-white border-t-2 border-amber-100 flex items-end space-x-3" @submit.prevent="send()">
            <input
                type="text"
                x-model="input"
                class="flex-1 text-sm border-2 border-slate-200 rounded-2xl px-5 py-3.5 focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-200 transition-all bg-slate-50 focus:bg-white font-semibold placeholder-slate-400 shadow-sm"
                placeholder="Напишите ваш вопрос..."
                :disabled="loading"
                @keydown.enter.prevent="send()"
            >
            <button
                type="submit"
                class="w-12 h-12 flex items-center justify-center rounded-2xl bg-gradient-to-br from-amber-500 to-orange-500 text-white hover:from-amber-600 hover:to-orange-600 hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed transition-all font-bold shadow-lg active:scale-95"
                :disabled="loading || !input.trim()"
                title="Отправить"
            >
                <span x-show="!loading">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                    </svg>
                </span>
                <span x-show="loading">
                    <svg class="w-6 h-6 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                </span>
            </button>
        </form>
    </div>

    {{-- Toggle Button - Chat Support Button (Circular with Icon) --}}
    <button
        @click="open = !open"
        class="relative w-16 h-16 md:w-20 md:h-20 rounded-full bg-gradient-to-br from-amber-500 to-orange-500 text-white flex items-center justify-center shadow-2xl shadow-amber-500/50 hover:shadow-amber-500/70 transition-all duration-300 hover:scale-110 active:scale-95 group border-4 border-white/20"
        title="Bikube Smart - Онлайн помощник"
        style="z-index: 50;"
    >
        {{-- Pulse animation when closed --}}
        <div x-show="!open" class="absolute inset-0 rounded-full bg-amber-400 animate-ping opacity-20"></div>
        
        {{-- Icon --}}
        <svg class="w-8 h-8 md:w-10 md:h-10 relative z-10 drop-shadow-lg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        
        {{-- Badge with message count --}}
        <span x-show="messages.length > 0" class="absolute -top-1 -right-1 w-6 h-6 md:w-7 md:h-7 bg-red-500 rounded-full text-[11px] md:text-xs font-black flex items-center justify-center text-white shadow-lg border-3 border-white z-20" x-text="messages.length > 99 ? '99+' : messages.length"></span>
        
        {{-- Tooltip --}}
        <span class="hidden md:block absolute right-full mr-4 top-1/2 -translate-y-1/2 px-4 py-2 bg-slate-900 text-white text-xs font-bold rounded-xl whitespace-nowrap opacity-0 group-hover:opacity-100 transition-all pointer-events-none shadow-xl z-30">
            Чат поддержки
        </span>
    </button>
</div>

<script>
/**
 * Bikube Assistant Alpine.js Component
 * Оптимизированный компонент для работы с ассистентом
 */
function bikubeAssistant(config) {
    return {
        open: false,
        input: '',
        loading: false,
        messages: [],
        error: null,
        config: {
            apiUrl: config.apiUrl || '/lk/assistant/message',
            csrfToken: config.csrfToken || '',
            storageKey: 'bikube_assistant_messages',
            maxMessages: 50,
            debounceDelay: 300,
        },
        debounceTimer: null,
        
        init() {
            this.loadMessages();
            this.$watch('messages', () => {
                this.debouncedSave();
            });
        },
        
        async send() {
            const text = this.input.trim();
            if (!text || this.loading) return;
            
            this.messages.push({ role: 'user', text, timestamp: new Date() });
            this.input = '';
            this.loading = true;
            this.error = null;
            
            this.$nextTick(() => {
                this.scrollToBottom();
            });
            
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
                
                this.messages.push({
                    role: 'assistant',
                    text: reply,
                    timestamp: new Date(),
                });
                
                // Ограничиваем количество сообщений
                if (this.messages.length > this.config.maxMessages) {
                    this.messages = this.messages.slice(-this.config.maxMessages);
                }
                
                this.$nextTick(() => {
                    this.scrollToBottom();
                });
            } catch (e) {
                console.error('Assistant error:', e);
                this.handleError(e);
            } finally {
                this.loading = false;
            }
        },
        
        handleError(error) {
            let errorMessage = '❌ Ошибка запроса к ассистенту. Попробуйте позже.';
            
            if (error.message && error.message.includes('Failed to fetch')) {
                errorMessage = '⚠️ Нет соединения с сервером. Проверьте интернет-соединение.';
            } else if (error.message && error.message.includes('HTTP error')) {
                errorMessage = '⚠️ Ошибка сервера. Попробуйте позже.';
            }
            
            this.messages.push({
                role: 'assistant',
                text: errorMessage,
                timestamp: new Date(),
            });
            
            this.$nextTick(() => {
                this.scrollToBottom();
            });
        },
        
        scrollToBottom() {
            this.$nextTick(() => {
                const el = this.$refs.messages;
                if (el) {
                    el.scrollTop = el.scrollHeight;
                }
            });
        },
        
        clearMessages() {
            if (this.messages.length === 0) return;
            if (confirm('🗑️ Очистить всю историю чата?')) {
                this.messages = [];
                try {
                    localStorage.removeItem(this.config.storageKey);
                } catch (e) {
                    console.warn('LocalStorage not available');
                }
            }
        },
        
        loadMessages() {
            try {
                const saved = localStorage.getItem(this.config.storageKey);
                if (saved) {
                    const parsed = JSON.parse(saved);
                    this.messages = parsed.slice(-this.config.maxMessages);
                }
            } catch (e) {
                console.warn('Failed to load messages from localStorage');
            }
        },
        
        debouncedSave() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                this.saveMessages();
            }, this.config.debounceDelay);
        },
        
        saveMessages() {
            try {
                if (this.messages.length > 0) {
                    localStorage.setItem(
                        this.config.storageKey,
                        JSON.stringify(this.messages)
                    );
                }
            } catch (e) {
                console.warn('Failed to save messages to localStorage');
            }
        },
        
        getSampleQuestions() {
            return [
                '📦 Какие у меня есть активные заказы?',
                '💰 Сколько я заработал сегодня?',
                '📊 Моя статистика',
                '📅 График работы',
            ];
        },
        
        sendSample(question) {
            this.input = question;
            this.$nextTick(() => {
                this.send();
            });
        },
        
        formatMessage(text) {
            return text
                .replace(/\n/g, '<br>')
                .replace(/\*\*([^*]+)\*\*/g, '<strong class="font-black">$1</strong>')
                .replace(/\*([^*]+)\*/g, '<em>$1</em>');
        }
    };
}
</script>
