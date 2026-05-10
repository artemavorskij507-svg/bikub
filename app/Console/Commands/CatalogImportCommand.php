<?php

namespace App\Console\Commands;

use App\Models\PricingRule;
use App\Models\ServiceCategory;
use App\Models\ServiceType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CatalogImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'catalog:import {services_file} {pricing_file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import service catalogs and pricing rules from JSON files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $servicesFile = $this->argument('services_file');
        $pricingFile = $this->argument('pricing_file');

        if (! file_exists($servicesFile)) {
            $this->error("Services file not found: {$servicesFile}");

            return 1;
        }

        if (! file_exists($pricingFile)) {
            $this->error("Pricing file not found: {$pricingFile}");

            return 1;
        }

        $this->info('Starting catalog import...');

        DB::transaction(function () use ($servicesFile, $pricingFile) {
            // Import services
            $this->importServices($servicesFile);

            // Import pricing rules
            $this->importPricingRules($pricingFile);
        });

        $this->info('Catalog import completed successfully!');
        $this->info('Categories: '.ServiceCategory::count());
        $this->info('Service Types: '.ServiceType::count());
        $this->info('Pricing Rules: '.PricingRule::count());

        return 0;
    }

    private function importServices(string $file)
    {
        $this->info('Importing services...');

        $services = json_decode(file_get_contents($file), true);

        if (! is_array($services)) {
            $this->error('Invalid services JSON format');

            return;
        }

        foreach ($services as $serviceData) {
            // Ensure category exists
            $category = ServiceCategory::firstOrCreate(
                ['code' => $serviceData['category']],
                [
                    'name' => ucfirst($serviceData['category']),
                    'description' => "Category for {$serviceData['category']} services",
                    'is_active' => true,
                ]
            );

            // Create or update service type
            ServiceType::updateOrCreate(
                ['code' => $serviceData['code']],
                [
                    'service_category_id' => $category->id,
                    'name' => $serviceData['name'],
                    'slug' => \Str::slug($serviceData['name']),
                    'description' => $serviceData['description'] ?? '',
                    'default_pricing' => $serviceData['default_pricing'] ?? [],
                    'skills' => $serviceData['skills'] ?? [],
                    'inventory' => $serviceData['inventory'] ?? [],
                    'is_active' => true,
                ]
            );

            $this->line("✓ Imported: {$serviceData['code']}");
        }
    }

    private function importPricingRules(string $file)
    {
        $this->info('Importing pricing rules...');

        $pricingData = json_decode(file_get_contents($file), true);

        if (! is_array($pricingData)) {
            $this->error('Invalid pricing JSON format');

            return;
        }

        foreach ($pricingData as $ruleData) {
            $serviceType = ServiceType::where('code', $ruleData['service_code'])->first();

            if (! $serviceType) {
                $this->warn("Service type not found: {$ruleData['service_code']}");

                continue;
            }

            PricingRule::updateOrCreate(
                [
                    'service_type_id' => $serviceType->id,
                    'name' => "Default pricing for {$serviceType->name}",
                ],
                [
                    'description' => "Imported pricing rule for {$serviceType->name}",
                    'base_price' => $ruleData['rules']['base'] ?? 0,
                    'currency' => 'NOK',
                    'pricing_model' => $ruleData['rules'],
                    'conditions' => $ruleData['conditions'] ?? [],
                    'modifiers' => $ruleData['modifiers'] ?? [],
                    'is_active' => true,
                ]
            );

            $this->line("✓ Pricing rule: {$ruleData['service_code']}");
        }
    }
}
