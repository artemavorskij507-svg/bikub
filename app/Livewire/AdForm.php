<?php

namespace App\Livewire;

use App\Modules\Classifieds\Models\AdCategory;
use App\Modules\Classifieds\Models\AdImage;
use App\Modules\Classifieds\Models\ClassifiedAd;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class AdForm extends Component
{
    use WithFileUploads;

    public $title = '';

    public $description = '';

    public $category_id = '';

    public $price_value = '';

    public $address = '';

    public $photos = []; // Temporary uploads

    protected $rules = [
        'title' => 'required|string|max:255',
        'category_id' => 'required|exists:ad_categories,id',
        'price_value' => 'nullable|numeric|min:0',
        'photos.*' => 'image|max:10240', // 10MB max per photo
    ];

    public function save()
    {
        $this->validate();

        $ad = new ClassifiedAd;
        $ad->user_id = auth()->id();
        $ad->category_id = $this->category_id;
        $ad->title = $this->title;
        $ad->description = $this->description;
        $ad->price_value = $this->price_value ? (int) ($this->price_value * 100) : null;
        $ad->address = $this->address;
        $ad->status = 'moderation';
        $ad->slug = Str::slug($this->title.'-'.Str::random(6));

        $ad->save();

        // Save Photos
        foreach ($this->photos as $index => $photo) {
            $path = $photo->store('ads', 'public');
            AdImage::create([
                'classified_ad_id' => $ad->id,
                'path' => $path,
                'sort_order' => $index,
            ]);
        }

        session()->flash('message', 'Ad created successfully!');

        return redirect()->route('classifieds.show', $ad->slug);
    }

    public function render()
    {
        $categories = AdCategory::where('is_active', true)->orderBy('name')->get();

        return view('livewire.ad-form', compact('categories'));
    }
}
