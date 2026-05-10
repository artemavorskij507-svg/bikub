<?php

namespace App\Livewire;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Filament\Notifications\Notification;
use Livewire\Component;

class SupportTicketChat extends Component
{
    public SupportTicket $ticket;

    public string $message = '';

    // Автообновление через wire:poll.5s в view

    // Автообновление каждые 5 секунд через wire:poll в view

    public function mount(SupportTicket $ticket)
    {
        $this->ticket = $ticket;
    }

    public function sendMessage()
    {
        $this->validate([
            'message' => 'required|string|max:5000',
        ]);

        SupportTicketMessage::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => auth()->id(),
            'sender_type' => 'support',
            'message' => $this->message,
            'read_at' => now(), // Свои сообщения считаем прочитанными
        ]);

        $this->message = '';

        // Компонент автоматически обновится через wire:poll.5s в view

        Notification::make()
            ->title('Сообщение отправлено')
            ->success()
            ->send();
    }

    public function markAsRead(SupportTicketMessage $message)
    {
        if ($message->sender_type === 'worker' && ! $message->read_at) {
            $message->update(['read_at' => now()]);

            Notification::make()
                ->title('Сообщение отмечено как прочитанное')
                ->success()
                ->send();
        }
    }

    public function render()
    {
        $messages = $this->ticket->messages()
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        return view('livewire.support-ticket-chat', [
            'messages' => $messages,
        ]);
    }
}
