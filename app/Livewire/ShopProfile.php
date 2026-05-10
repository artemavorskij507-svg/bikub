<?php

namespace App\Livewire;

use App\Modules\Classifieds\Models\Shop;
use Livewire\Component;
use Livewire\WithPagination;

class ShopProfile extends Component
{
    use WithPagination;

    public string $slug;

    public Shop $shop;

    public function mount(string $slug): void
    {
        $this->slug = $slug;
        $this->shop = Shop::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();
    }

    public function render()
    {
        $ads = $this->shop->ads()
            ->published()
            ->orderByDesc('vip_expires_at')
            ->orderByDesc('published_at')
            ->paginate(12);

        return view('livewire.shop-profile', [
            'shop' => $this->shop,
            'ads' => $ads,
        ]);
    }
}
