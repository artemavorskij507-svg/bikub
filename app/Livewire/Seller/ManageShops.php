<?php

namespace App\Livewire\Seller;

use App\Modules\Classifieds\Models\Shop;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class ManageShops extends Component
{
    use WithFileUploads;

    public $isCreating = false;

    public $editingId = null;

    // Form Fields
    public $name;

    public $description;

    public $phone;

    public $address;

    public $website;

    public $logo;

    public $cover;

    public function render()
    {
        if (! Schema::hasTable('shops')) {
            return view('livewire.seller.manage-shops', ['shop' => null]);
        }

        $shop = Shop::where('user_id', Auth::id())->first();

        return view('livewire.seller.manage-shops', ['shop' => $shop]);
    }

    public function create()
    {
        $this->isCreating = true;
        $this->reset(['name', 'description', 'phone', 'address', 'website', 'logo', 'cover', 'editingId']);
    }

    public function edit()
    {
        if (! Schema::hasTable('shops')) {
            session()->flash('message', 'Shop profile is unavailable: database schema is incomplete.');

            return;
        }

        $shop = Shop::where('user_id', Auth::id())->firstOrFail();
        $this->editingId = $shop->id;
        $this->name = $shop->name;
        $this->description = $shop->description;
        $this->phone = $shop->phone;
        $this->address = $shop->address;
        $this->website = $shop->website;
        $this->isCreating = true;
    }

    public function save()
    {
        if (! Schema::hasTable('shops')) {
            session()->flash('message', 'Shop profile is unavailable: database schema is incomplete.');

            return;
        }

        $this->validate([
            'name' => 'required|min:3|max:50',
            'description' => 'required|max:500',
            'logo' => 'nullable|image|max:2048', // 2MB
            'cover' => 'nullable|image|max:4096', // 4MB
        ]);

        $data = [
            'user_id' => Auth::id(),
            'name' => $this->name,
            'description' => $this->description,
            'phone' => $this->phone,
            'address' => $this->address,
            'website' => $this->website,
            'is_active' => true,
        ];

        if ($this->editingId) {
            $shop = Shop::find($this->editingId);
            if (! $shop || $shop->user_id !== Auth::id()) {
                abort(403);
            }

            // Keep old slug if not updating
            unset($data['slug']);

            if ($this->logo) {
                // Delete old logo if exists
                if ($shop->logo_path && file_exists(storage_path('app/public/'.$shop->logo_path))) {
                    unlink(storage_path('app/public/'.$shop->logo_path));
                }
                $data['logo_path'] = $this->logo->store('shops/logos', 'public');
            }

            if ($this->cover) {
                // Delete old cover if exists
                if ($shop->cover_path && file_exists(storage_path('app/public/'.$shop->cover_path))) {
                    unlink(storage_path('app/public/'.$shop->cover_path));
                }
                $data['cover_path'] = $this->cover->store('shops/covers', 'public');
            }

            $shop->update($data);
            $message = 'Профиль магазина обновлён!';
        } else {
            $data['slug'] = Str::slug($this->name.'-'.Str::random(4));

            if ($this->logo) {
                $data['logo_path'] = $this->logo->store('shops/logos', 'public');
            }
            if ($this->cover) {
                $data['cover_path'] = $this->cover->store('shops/covers', 'public');
            }

            Shop::create($data);
            $message = 'Магазин успешно создан!';
        }

        $this->isCreating = false;
        $this->reset(['name', 'description', 'phone', 'address', 'website', 'logo', 'cover', 'editingId']);
        session()->flash('message', $message);
    }

    public function cancel()
    {
        $this->isCreating = false;
        $this->reset(['name', 'description', 'phone', 'address', 'website', 'logo', 'cover', 'editingId']);
    }
}
