@if(isset($ticket) && $ticket)
    <div class="w-full">
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
                ЧАТ
            </h3>
        </div>
        @livewire('support-ticket-chat', ['ticket' => $ticket], key('chat-' . $ticket->id))
    </div>
@else
    <div class="p-4 text-center text-gray-500">
        Тикет не найден
    </div>
@endif

