<?php

namespace App\Livewire;

use App\Modules\Classifieds\Models\ClassifiedAd;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class MyAds extends Component
{
    use WithPagination;

    public $filter = 'all'; // all, published, moderation, draft, sold

    public function setFilter($status)
    {
        $this->filter = $status;
        $this->resetPage();
    }

    public function delete($id)
    {
        $ad = ClassifiedAd::where('user_id', Auth::id())->findOrFail($id);
        $ad->delete();

        session()->flash('message', 'Объявление успешно удалено.');
    }

    public function render()
    {
        $hasAdImagesTable = Schema::hasTable('ad_images');

        $query = ClassifiedAd::where('user_id', Auth::id())
            ->with(['category', 'shop']);

        if ($this->filter !== 'all') {
            $query->where('status', $this->filter);
        }

        if ($hasAdImagesTable) {
            $query->with(['images']);
        }

        $ads = $query->orderByDesc('created_at')->paginate(10);

        if (! $hasAdImagesTable) {
            $ads->getCollection()->each(function (ClassifiedAd $ad): void {
                $ad->setRelation('images', collect());
            });
        }

        return view('livewire.my-ads', [
            'ads' => $ads,
            'stats' => [
                'total' => ClassifiedAd::where('user_id', Auth::id())->count() ?? 0,
                'views' => ClassifiedAd::where('user_id', Auth::id())->sum('views_count') ?? 0,
            ],
        ]);
    }
}
