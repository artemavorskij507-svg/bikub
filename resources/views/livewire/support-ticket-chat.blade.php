<div class="space-y-4" 
     x-data="{ 
         message: @entangle('message'),
         isScrolling: false,
         scrollToBottom() {
             this.$nextTick(() => {
                 const container = this.$refs.messagesContainer;
                 if (container) {
                     container.scrollTo({
                         top: container.scrollHeight,
                         behavior: 'smooth'
                     });
                 }
                 this.isScrolling = false;
             });
         },
         handleScroll() {
             const container = this.$refs.messagesContainer;
             if (container) {
                 const isAtBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 100;
                 this.isScrolling = !isAtBottom;
             }
         },
         init() {
             this.scrollToBottom();
             this.$watch('message', () => this.scrollToBottom());
         }
     }" 
     wire:poll.5s>
    <!-- Чат контейнер -->
    <div class="bg-white rounded-2xl border-2 border-gray-200 shadow-2xl overflow-hidden relative" 
         style="max-height: 700px; display: flex; flex-direction: column;">
        <!-- Заголовок чата -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 border-b-2 border-blue-500/50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-white">ЧАТ</h3>
                        <p class="text-xs text-blue-100 font-medium">Общение в реальном времени</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 bg-white/20 backdrop-blur-md px-3 py-1.5 rounded-lg border border-white/30">
                    <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse shadow-lg shadow-green-400/50"></span>
                    <span class="text-xs font-bold text-white">Онлайн</span>
                </div>
            </div>
        </div>

        <!-- Сообщения -->
        <div class="flex-1 overflow-y-auto p-6 space-y-4 bg-gray-50" 
             x-ref="messagesContainer"
             @scroll="handleScroll()"
             style="scrollbar-width: thin; scrollbar-color: rgba(156, 163, 175, 0.5) transparent;">
            @forelse($messages as $message)
                @php
                    $isFromWorker = $message->sender_type === 'worker';
                    $isFromStaff = in_array($message->sender_type, ['dispatcher', 'admin', 'support'], true);
                    $isSystem = $message->sender_type === 'system';
                    $isCurrentUser = $message->user_id === auth()->id();
                @endphp

                <div class="flex {{ $isFromWorker || $isSystem ? 'justify-start' : 'justify-end' }} gap-3 group" 
                     x-data="{ showActions: false }"
                     @mouseenter="showActions = true"
                     @mouseleave="showActions = false">
                    @if($isFromWorker || $isSystem)
                        <!-- Аватар слева для работника/системы -->
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-white text-sm font-black shadow-lg transition-transform duration-300 group-hover:scale-110 border-2
                                {{ $isSystem ? 'bg-gradient-to-br from-purple-600 to-purple-700 border-purple-400/50' : 'bg-gradient-to-br from-green-500 to-emerald-600 border-green-400/50' }}">
                                @if($isSystem)
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                @else
                                    {{ strtoupper(mb_substr($message->user->name ?? 'Г', 0, 1)) }}
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Сообщение -->
                    <div class="flex flex-col {{ $isFromWorker || $isSystem ? 'items-start' : 'items-end' }} max-w-[75%]">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-sm font-black
                                {{ $isSystem ? 'text-purple-700' : ($isFromWorker ? 'text-green-700' : 'text-blue-700') }}">
                                @if($isSystem)
                                    Система
                                @else
                                    {{ $message->user->name ?? 'Гость' }}
                                @endif
                            </span>
                            <span class="text-xs text-gray-600 font-medium">
                                {{ $message->created_at->format('d.m.Y H:i') }}
                            </span>
                            @if($isFromWorker && !$message->read_at)
                                <button 
                                    wire:click="markAsRead({{ $message->id }})"
                                    class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-yellow-500/20 text-yellow-300 border border-yellow-500/50 hover:bg-yellow-500/30 transition-all duration-200">
                                    Не прочитано
                                </button>
                            @elseif($message->read_at)
                                <span class="text-xs text-gray-600 font-medium flex items-center gap-1">
                                    <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    Прочитано {{ $message->read_at->format('d.m H:i') }}
                                </span>
                            @endif
                        </div>
                        
                        <div class="px-5 py-3 rounded-2xl shadow-lg transition-all duration-300 group-hover:shadow-xl
                            {{ $isSystem 
                                ? 'bg-purple-100 border-2 border-purple-300 text-gray-900 bubble-left hover:ring-2 hover:ring-purple-300' 
                                : ($isFromWorker 
                                    ? 'bg-green-100 border-2 border-green-300 text-gray-900 bubble-left hover:ring-2 hover:ring-green-300' 
                                    : 'bg-blue-100 border-2 border-blue-300 text-gray-900 bubble-right hover:ring-2 hover:ring-blue-300') }}">
                            <p class="text-sm whitespace-pre-wrap font-medium leading-relaxed text-gray-900">{{ $message->message }}</p>
                        </div>

                        @if($message->metadata && !empty($message->metadata))
                            <div class="mt-2 text-xs text-gray-600 bg-gray-100 rounded-lg p-3 border border-gray-200">
                                @foreach($message->metadata as $key => $value)
                                    <span class="inline-block mr-3 mb-1">
                                        <strong class="text-gray-800 font-bold">{{ $key }}:</strong> 
                                        <span class="text-gray-700">{{ is_array($value) ? json_encode($value) : $value }}</span>
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    @if($isFromStaff && !$isSystem)
                        <!-- Аватар справа для поддержки -->
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-white text-sm font-black bg-gradient-to-br from-blue-500 via-indigo-500 to-purple-600 shadow-lg transition-transform duration-300 group-hover:scale-110 border-2 border-blue-400/50">
                                {{ strtoupper(mb_substr($message->user->name ?? 'П', 0, 1)) }}
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center py-16">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-blue-100 border-2 border-blue-300 mb-6">
                        <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-black text-gray-800 mb-2">Нет сообщений</h3>
                    <p class="text-sm text-gray-600 font-medium">Начните переписку!</p>
                </div>
            @endforelse
        </div>

        <!-- Кнопка прокрутки вниз -->
        <div x-show="isScrolling" 
             x-cloak
             class="absolute bottom-20 right-6 z-20">
            <button @click="scrollToBottom()" 
                    class="w-14 h-14 rounded-full bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-600 text-white shadow-2xl hover:shadow-blue-500/50 hover:scale-110 transition-all duration-300 flex items-center justify-center border-2 border-blue-400/50 backdrop-blur-md">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                </svg>
            </button>
        </div>
    </div>

    <!-- Форма отправки сообщения -->
    <div class="bg-white rounded-2xl border-2 border-gray-200 p-5 shadow-xl">
        <div class="flex gap-3">
            <div class="flex-1 relative">
                <textarea 
                    wire:model="message"
                    x-model="message"
                    placeholder="Введите сообщение..."
                    rows="3"
                    class="w-full rounded-xl border-2 border-gray-300 bg-gray-50 text-gray-900 placeholder-gray-500 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/50 focus:bg-white transition-all duration-200 resize-none font-medium"
                    maxlength="5000"
                    @keydown.enter.prevent="if(!$event.shiftKey && message.trim()) { $wire.sendMessage(); }"></textarea>
                <div class="absolute bottom-2 right-3 flex items-center gap-3">
                    <p class="text-xs text-gray-600 font-medium" x-text="`${message.length}/5000`"></p>
                    <span class="text-xs text-gray-500">Shift+Enter для новой строки</span>
                </div>
            </div>
            <div class="flex items-end">
                <button 
                    type="button"
                    wire:click="sendMessage"
                    wire:loading.attr="disabled"
                    :disabled="!message.trim()"
                    class="px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl font-black shadow-lg hover:shadow-xl transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed hover:scale-105 flex items-center gap-2">
                    <span wire:loading.remove wire:target="sendMessage" class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                        Отправить
                    </span>
                    <span wire:loading wire:target="sendMessage" class="flex items-center gap-2">
                        <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Отправка...
                    </span>
                </button>
            </div>
        </div>
    </div>
    
    <style>
        [x-cloak] { display: none !important; }
        [x-ref="messagesContainer"]::-webkit-scrollbar {
            width: 8px;
        }
        [x-ref="messagesContainer"]::-webkit-scrollbar-track {
            background: rgba(243, 244, 246, 0.5);
            border-radius: 4px;
        }
        [x-ref="messagesContainer"]::-webkit-scrollbar-thumb {
            background: rgba(156, 163, 175, 0.5);
            border-radius: 4px;
        }
        [x-ref="messagesContainer"]::-webkit-scrollbar-thumb:hover {
            background: rgba(107, 114, 128, 0.8);
        }
        .bubble-left {border-top-left-radius:0.25rem !important;}
        .bubble-right {border-top-right-radius:0.25rem !important;}
    </style>
</div>
