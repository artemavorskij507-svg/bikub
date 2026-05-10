<?php

namespace App\Modules\Classifieds\Http\Livewire;

use App\Modules\Classifieds\Models\AdCategory;
use App\Modules\Classifieds\Models\ClassifiedAd;
use Livewire\Component;
use Livewire\WithPagination;

class UserAdsTable extends Component
{
    use WithPagination;

    public $search = '';

    public $status = null;

    public $category = null;

    public $radius = null; // km – future PostGIS filter

    protected $updatesQueryString = ['search', 'status', 'category', 'radius'];

    public function render()
    {
        $query = ClassifiedAd::where('user_id', auth()->id())
            ->when($this->search, fn ($q) => $q->where('title', 'ilike', "%{$this->search}%"))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->category, fn ($q) => $q->where('category_id', $this->category));

        // TODO: add PostGIS radius filter using $this->radius

        $ads = $query->orderByDesc('created_at')->paginate(12);

        return view('classifieds.livewire.user-ads-table', [
            'ads' => $ads,
            'categories' => AdCategory::where('is_active', true)->get(),
        ]);
    }
}
