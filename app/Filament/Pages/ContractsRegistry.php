<?php

namespace App\Filament\Pages;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ContractsRegistry extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Contracts';
    protected static ?string $navigationGroup = 'HR';
    protected static string $view = 'filament.pages.contracts-registry';

    public array $contracts = [];

    public function mount(): void
    {
        $this->reload();
    }

    public function reload(): void
    {
        if (! Schema::hasTable('contracts')) {
            $this->contracts = [];
            return;
        }
        $this->contracts = DB::table('contracts')->orderByDesc('id')->limit(100)->get()->map(fn ($r) => (array) $r)->toArray();
    }

    public function markSigned(int $id): void
    {
        if (! Schema::hasTable('contracts')) {
            return;
        }
        DB::table('contracts')->where('id', $id)->update(['status' => 'signed', 'signed_at' => now(), 'updated_at' => now()]);
        if (Schema::hasTable('contract_events')) {
            DB::table('contract_events')->insert([
                'contract_id' => $id,
                'event_type' => 'signed',
                'payload' => json_encode(['source' => 'manual_admin']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        Notification::make()->title('Contract marked as signed')->success()->send();
        $this->reload();
    }
}

