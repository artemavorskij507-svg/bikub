<?php

namespace App\Livewire;

use App\Modules\Classifieds\Models\ClassifiedAd;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AdPromotionModal extends Component
{
    public $show = false;

    public $adId = null;

    public $selectedPromotion = 'bump'; // bump, highlight, top, vip

    protected $listeners = ['open-promotion-modal' => 'openModal'];

    public function openModal($adId)
    {
        $this->adId = $adId;
        $this->show = true;
    }

    public function closeModal()
    {
        $this->show = false;
        $this->adId = null;
        $this->selectedPromotion = 'bump';
    }

    public function promote()
    {
        if (! $this->adId) {
            return;
        }

        $ad = ClassifiedAd::where('user_id', Auth::id())
            ->findOrFail($this->adId);

        $now = now();

        switch ($this->selectedPromotion) {
            case 'bump':
                $ad->update(['bumped_at' => $now]);
                $message = 'Объявление поднято в топ!';
                break;
            case 'highlight':
                $ad->update(['highlight_expires_at' => $now->addDays(7)]);
                $message = 'Объявление выделено на 7 дней!';
                break;
            case 'top':
                $ad->update(['top_expires_at' => $now->addDays(3)]);
                $message = 'Объявление размещено в топ на 3 дня!';
                break;
            case 'vip':
                $ad->update(['vip_expires_at' => $now->addDays(14)]);
                $message = 'VIP статус активирован на 14 дней!';
                break;
            default:
                $message = 'Неверный тип продвижения';
        }

        $this->closeModal();
        session()->flash('promotion-success', $message);
        $this->dispatch('promotion-applied');
    }

    public function render()
    {
        return view('livewire.ad-promotion-modal');
    }
}
