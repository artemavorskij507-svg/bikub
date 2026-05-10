<?php

namespace App\Filament\Resources\AdImportResource\Pages;

use App\Filament\Resources\AdImportResource;
use App\Modules\Classifieds\Models\AdImport;
use App\Models\User;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ListAdImports extends ListRecords
{
    protected static string $resource = AdImportResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->ensureLocalAdImportsSchema();
        $this->seedLocalDemoImportIfEmpty();
    }

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function ensureLocalAdImportsSchema(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        if (Schema::hasTable('ad_imports')) {
            return;
        }

        Schema::create('ad_imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->string('file_path');
            $table->string('file_type')->default('xml');
            $table->string('status')->default('pending');
            $table->integer('processed_count')->default(0);
            $table->integer('error_count')->default(0);
            $table->json('report')->nullable();
            $table->timestamps();
        });
    }

    protected function seedLocalDemoImportIfEmpty(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        if (! Schema::hasTable('ad_imports')) {
            return;
        }

        if (AdImport::query()->exists()) {
            return;
        }

        $userId = Auth::id() ?? User::query()->value('id');

        // In some environments ad_imports.user_id is NOT NULL.
        if ($userId === null) {
            return;
        }

        $payload = [
            'user_id' => $userId,
            'file_path' => 'imports/demo-feed.xml',
            'file_type' => 'xml',
            'status' => 'completed',
            'processed_count' => 24,
            'error_count' => 1,
            'report' => [
                'source' => 'local_demo_seed',
                'note' => 'Auto-generated demo import for local admin list',
            ],
        ];

        if (Schema::hasColumn('ad_imports', 'shop_id') && Schema::hasTable('shops')) {
            $shopId = DB::table('shops')->value('id');
            if ($shopId !== null) {
                $payload['shop_id'] = $shopId;
            }
        }

        AdImport::query()->create($payload);
    }
}
