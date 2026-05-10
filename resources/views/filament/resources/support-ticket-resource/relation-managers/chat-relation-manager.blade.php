<div class="fi-relation-manager">
    <div class="space-y-6">
        <div>
            @livewire(\App\Livewire\SupportTicketChat::class, ['ticket' => $ticket], key('chat-' . $ticket->id))
        </div>
    </div>
</div>

