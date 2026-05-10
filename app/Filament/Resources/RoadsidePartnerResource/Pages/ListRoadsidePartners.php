<?php

namespace App\Filament\Resources\RoadsidePartnerResource\Pages;

use App\Filament\Resources\RoadsidePartnerResource;
use App\Models\Partner;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ListRoadsidePartners extends ListRecords
{
    protected static string $resource = RoadsidePartnerResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->seedRoadsidePartnersIfEmpty();
    }

    protected function getActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    protected function seedRoadsidePartnersIfEmpty(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        if (! Schema::hasTable('partners')) {
            return;
        }

        if (Partner::query()->roadside()->exists()) {
            return;
        }

        $rows = [
            [
                'name' => 'Oslo Tow Rapid',
                'slug' => 'oslo-tow-rapid',
                'type' => 'towing_service',
                'description' => 'Городская эвакуация 24/7.',
                'contact_person' => 'Anders Nilsen',
                'phone' => '+47 22 00 11 01',
                'email' => 'ops@oslotowrapid.no',
                'address' => 'Alna 45, Oslo',
                'metadata' => ['source' => 'local_demo_seed'],
            ],
            [
                'name' => 'Roadside Mobile East',
                'slug' => 'roadside-mobile-east',
                'type' => 'roadside_mobile',
                'description' => 'Выездная помощь: запуск, колесо, топливо.',
                'contact_person' => 'Mia Solberg',
                'phone' => '+47 22 00 11 02',
                'email' => 'dispatch@roadsideeast.no',
                'address' => 'Østensjøveien 22, Oslo',
                'metadata' => ['source' => 'local_demo_seed'],
            ],
            [
                'name' => 'Nordic Inspection Hub',
                'slug' => 'nordic-inspection-hub',
                'type' => 'inspection_center',
                'description' => 'Осмотр авто перед покупкой.',
                'contact_person' => 'Lars Holm',
                'phone' => '+47 22 00 11 03',
                'email' => 'service@nordicinspection.no',
                'address' => 'Bjørvika 10, Oslo',
                'metadata' => ['source' => 'local_demo_seed'],
            ],
        ];

        $optionalColumns = [
            'active' => true,
            'is_active' => true,
            'is_available' => true,
            'priority' => 100,
            'capabilities' => ['towing'],
        ];

        foreach ($rows as $row) {
            foreach ($optionalColumns as $column => $value) {
                if (Schema::hasColumn('partners', $column)) {
                    $row[$column] = $value;
                }
            }

            if (Schema::hasColumn('partners', 'domain') && ! isset($row['domain'])) {
                $row['domain'] = Str::slug($row['slug']).'.local';
            }

            if (Schema::hasColumn('partners', 'uuid') && ! isset($row['uuid'])) {
                $row['uuid'] = (string) Str::uuid();
            }

            Partner::query()->firstOrCreate(
                ['slug' => $row['slug']],
                $row
            );
        }
    }
}
