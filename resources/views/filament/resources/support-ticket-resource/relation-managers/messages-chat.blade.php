<div class="space-y-4" x-data="{ 
    sending: false,
    message: '',
    scrollToBottom() {
        this.$nextTick(() => {
            const container = this.$refs.messagesContainer;
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        });
    }
}" x-init="scrollToBottom()" @livewire:poll="scrollToBottom()">
    <!-- Чат контейнер -->
    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700" style="max-height: 600px; overflow-y: auto;" x-ref="messagesContainer">
        <div class="p-4 space-y-4">
            @forelse($livewire->getTableRecords() as $message)
                @php
                    $isFromWorker = $message->sender_type === 'worker';
                    $isFromStaff = in_array($message->sender_type, ['dispatcher', 'admin', 'support'], true);
                    $isSystem = $message->sender_type === 'system';
                    $isCurrentUser = $message->user_id === auth()->id();
                @endphp

                <div class="flex {{ $isFromWorker || $isSystem ? 'justify-start' : 'justify-end' }} gap-3">
                    @if($isFromWorker || $isSystem)
                        <!-- Аватар слева для работника/системы -->
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-semibold
                                {{ $isSystem ? 'bg-gray-500' : 'bg-green-500' }}">
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
                    <div class="flex flex-col {{ $isFromWorker || $isSystem ? 'items-start' : 'items-end' }} max-w-[70%]">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                @if($isSystem)
                                    Система
                                @else
                                    {{ $message->user->name ?? 'Гость' }}
                                @endif
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $message->created_at->format('d.m.Y H:i') }}
                            </span>
                            @if($isFromWorker && !$message->read_at)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                    Не прочитано
                                </span>
                            @elseif($message->read_at)
                                <span class="text-xs text-gray-400 dark:text-gray-500">
                                    Прочитано {{ $message->read_at->format('d.m H:i') }}
                                </span>
                            @endif
                        </div>
                        
                        <div class="px-4 py-2 rounded-lg shadow-sm
                            {{ $isFromWorker || $isSystem 
                                ? 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700' 
                                : 'bg-blue-500 text-white' }}">
                            <p class="text-sm whitespace-pre-wrap">{{ $message->message }}</p>
                        </div>

                        @if($message->metadata && !empty($message->metadata))
                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                @foreach($message->metadata as $key => $value)
                                    <span class="inline-block mr-2">
                                        <strong>{{ $key }}:</strong> {{ is_array($value) ? json_encode($value) : $value }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    @if($isFromStaff && !$isSystem)
                        <!-- Аватар справа для поддержки -->
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-semibold bg-blue-500">
                                {{ strtoupper(mb_substr($message->user->name ?? 'П', 0, 1)) }}
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                    <p class="mt-2 text-sm">Нет сообщений. Начните переписку!</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Форма отправки сообщения -->
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex gap-2">
            <div class="flex-1">
                <textarea 
                    x-model="message"
                    placeholder="Введите сообщение..."
                    rows="3"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    maxlength="5000"></textarea>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="`${message.length}/5000`"></p>
            </div>
            <div class="flex items-end">
                <button 
                    type="button"
                    @click="
                        sending = true;
                        $wire.call('create').then(() => {
                            message = '';
                            sending = false;
                            scrollToBottom();
                        });
                    "
                    :disabled="sending || !message.trim()"
                    class="px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-medium shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!sending">Отправить</span>
                    <span x-show="sending" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Отправка...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

