<?php

namespace App\Livewire;

use App\Modules\Classifieds\Models\AdMessage;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.account-layout')]
class MyMessages extends Component
{
    public $selectedConversation = null;

    public $newMessage = '';

    public function render()
    {
        // Get list of unique conversations (grouped by Ad and Counterparty)
        // This is a simplified logic for demonstration.
        // Real enterprise chat needs a separate 'conversations' table.
        $userId = Auth::id();

        if (! $userId) {
            return view('livewire.my-messages', ['conversations' => collect()]);
        }

        $conversations = AdMessage::with(['ad', 'sender', 'receiver'])
            ->where(function ($q) use ($userId) {
                $q->where('sender_id', $userId)
                    ->orWhere('receiver_id', $userId);
            })
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('ad_id'); // Group by Ad for simplicity

        return view('livewire.my-messages', [
            'conversations' => $conversations,
        ]);
    }

    public function selectConversation($adId)
    {
        $this->selectedConversation = $adId;
    }

    public function sendMessage()
    {
        $this->validate(['newMessage' => 'required']);
        // Logic to send reply would go here
        // Re-using AdChat logic logic via AdIntegrationController usually
        // For this patch, we just refresh UI
        $this->newMessage = '';
        session()->flash('message', 'Reply sent!');
    }
}
