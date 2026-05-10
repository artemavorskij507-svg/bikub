<?php

namespace App\Livewire;

use App\Modules\Classifieds\Models\AdMessage;
use App\Modules\Classifieds\Models\ClassifiedAd;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AdChat extends Component
{
    public int $adId;

    public string $newMessage = '';

    public int $receiverId;

    public function mount(ClassifiedAd $ad): void
    {
        $this->adId = $ad->id;
        // Для простоты: всегда пишем владельцу объявления
        $this->receiverId = (int) $ad->user_id;
    }

    public function sendMessage(): void
    {
        $this->validate([
            'newMessage' => ['required', 'string', 'max:1000'],
        ]);

        if (! Auth::check()) {
            redirect()->route('login');

            return;
        }

        AdMessage::create([
            'ad_id' => $this->adId,
            'sender_id' => Auth::id(),
            'receiver_id' => $this->receiverId,
            'message' => $this->newMessage,
        ]);

        $this->newMessage = '';

        $this->dispatch('message-sent');

        // Обновляем список сообщений
        $this->render();
    }

    public function render()
    {
        $userId = Auth::id();

        $messages = AdMessage::query()
            ->where('ad_id', $this->adId)
            ->when($userId, function ($q) use ($userId) {
                $q->where(function ($q2) use ($userId) {
                    $q2->where('sender_id', $userId)
                        ->orWhere('receiver_id', $userId);
                });
            })
            ->orderBy('created_at')
            ->get();

        return view('livewire.ad-chat', [
            'messages' => $messages,
        ]);
    }
}
