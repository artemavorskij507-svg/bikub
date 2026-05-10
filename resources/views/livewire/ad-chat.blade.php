<div class="bg-gray-50 border rounded-lg p-4 h-96 flex flex-col" data-chat-container>
    <h3 class="font-bold border-b pb-2 mb-2 text-gray-900">Чат с продавцом</h3>

    <div class="flex-grow overflow-y-auto space-y-3 mb-4 p-2" id="chat-messages">
        @forelse($messages as $msg)
            <div class="flex {{ $msg->sender_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
                <div class="{{ $msg->sender_id === auth()->id() ? 'bg-primary-600 text-white' : 'bg-white border border-gray-200 text-gray-900' }} rounded-lg px-4 py-2 max-w-xs text-sm shadow-sm">
                    <p class="break-words">{{ $msg->message }}</p>
                    <span class="text-xs {{ $msg->sender_id === auth()->id() ? 'text-primary-100' : 'text-gray-400' }} mt-1 block">
                        {{ $msg->created_at->format('H:i') }}
                    </span>
                </div>
            </div>
        @empty
            <div class="text-center text-gray-400 text-sm py-8">
                <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
                <p>Сообщений ещё нет. Начните переписку!</p>
            </div>
        @endforelse
    </div>

    <div class="mt-auto">
        <form wire:submit="sendMessage" class="flex gap-2">
            <input wire:model="newMessage" 
                   type="text" 
                   data-chat-input
                   class="flex-grow border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent" 
                   placeholder="Напишите сообщение...">
            <button type="submit" class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition-colors font-medium">
                Отправить
            </button>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('message-sent', () => {
            const container = document.getElementById('chat-messages');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        });
    });
</script>
@endpush
