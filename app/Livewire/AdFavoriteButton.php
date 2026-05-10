<?php

namespace App\Livewire;

use App\Modules\Classifieds\Models\ClassifiedAd;
use App\Modules\Classifieds\Models\ClassifiedAdFavorite;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AdFavoriteButton extends Component
{
    public ClassifiedAd $ad;

    public bool $isFavorite = false;

    public function mount(ClassifiedAd $ad)
    {
        $this->ad = $ad;
        $this->checkFavorite();
    }

    public function toggleFavorite()
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        if ($this->isFavorite) {
            ClassifiedAdFavorite::where('user_id', Auth::id())
                ->where('classified_ad_id', $this->ad->id)
                ->delete();
            $this->isFavorite = false;
            session()->flash('favorite-removed', 'Объявление удалено из избранного');
        } else {
            ClassifiedAdFavorite::create([
                'user_id' => Auth::id(),
                'classified_ad_id' => $this->ad->id,
            ]);
            $this->isFavorite = true;
            session()->flash('favorite-added', 'Объявление добавлено в избранное');
        }
    }

    protected function checkFavorite()
    {
        if (Auth::check()) {
            $this->isFavorite = ClassifiedAdFavorite::where('user_id', Auth::id())
                ->where('classified_ad_id', $this->ad->id)
                ->exists();
        }
    }

    public function render()
    {
        return view('livewire.ad-favorite-button');
    }
}
