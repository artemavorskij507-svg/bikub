<?php

namespace App\Livewire;

use App\Modules\Classifieds\Models\ClassifiedAdFavorite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.account-layout')]
class Favorites extends Component
{
    public function render()
    {
        $user = Auth::user();

        if (! $user) {
            return view('livewire.favorites', ['favorites' => collect()]);
        }

        // Graceful fallback for environments where classifieds migrations were not applied yet.
        if (! Schema::hasTable('classified_ad_favorites') || ! Schema::hasTable('classified_ads')) {
            return view('livewire.favorites', ['favorites' => collect()]);
        }

        $favorites = ClassifiedAdFavorite::where('user_id', $user->id)
            ->with(['classifiedAd' => function ($q) {
                $q->with(['category', 'shop', 'user', 'images']);
            }])
            ->whereHas('classifiedAd', function ($q) {
                $q->where('status', 'published');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('livewire.favorites', compact('favorites'));
    }

    public function removeFavorite($favoriteId)
    {
        if (! Schema::hasTable('classified_ad_favorites')) {
            return;
        }

        ClassifiedAdFavorite::where('user_id', Auth::id())
            ->where('id', $favoriteId)
            ->delete();
        session()->flash('message', 'Объявление удалено из избранного.');
    }
}
