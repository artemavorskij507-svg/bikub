<?php

namespace App\Filament\Pages;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class LandingPages extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Landing Pages / CMS';
    protected static ?string $navigationGroup = 'Catalog / Каталог';
    protected static string $view = 'filament.pages.landing-pages';

    public array $pages = [];
    public string $formSlug = '';
    public string $formTitle = '';
    public string $formContent = '';

    public function mount(): void
    {
        $this->reload();
    }

    public function reload(): void
    {
        if (! Schema::hasTable('cms_pages')) {
            $this->pages = [];
            return;
        }
        $this->pages = DB::table('cms_pages')->orderByDesc('updated_at')->limit(100)->get()->map(fn ($r) => (array) $r)->toArray();
    }

    public function save(): void
    {
        if (! Schema::hasTable('cms_pages')) {
            return;
        }
        if ($this->formSlug === '' || $this->formTitle === '') {
            return;
        }

        $existing = DB::table('cms_pages')->where('slug', $this->formSlug)->first();
        $payload = ['blocks' => [['type' => 'hero_text', 'text' => $this->formContent]]];
        if ($existing) {
            DB::table('cms_pages')->where('id', $existing->id)->update([
                'title' => $this->formTitle,
                'content' => json_encode($payload),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('cms_pages')->insert([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'slug' => $this->formSlug,
                'title' => $this->formTitle,
                'content' => json_encode($payload),
                'locale' => 'ru',
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        Notification::make()->title('Landing page saved')->success()->send();
        $this->formSlug = $this->formTitle = $this->formContent = '';
        $this->reload();
    }
}
