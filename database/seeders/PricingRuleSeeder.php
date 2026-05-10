<?php

namespace Database\Seeders;

use App\Models\GeoZone;
use App\Models\PricingRule;
use App\Models\ServiceType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PricingRuleSeeder extends Seeder
{
    public function run(): void
    {
        // Get service types
        $serviceTypes = ServiceType::all()->keyBy('slug');

        // Care L1 Services
        if (isset($serviceTypes['care-l1-med-delivery'])) {
            $this->createPricingRule($serviceTypes['care-l1-med-delivery'], 349.00, 'Dostavka lekarstv');
        }
        if (isset($serviceTypes['care-l1-errands'])) {
            $this->createPricingRule($serviceTypes['care-l1-errands'], 399.00, 'Porucheniya');
        }
        if (isset($serviceTypes['care-l1-stairs-firewood'])) {
            $this->createPricingRule($serviceTypes['care-l1-stairs-firewood'], 449.00, 'Zanos na etazh');
        }
        if (isset($serviceTypes['care-l1-light-fix'])) {
            $this->createPricingRule($serviceTypes['care-l1-light-fix'], 299.00, 'Lampochka');
        }
        if (isset($serviceTypes['care-l1-companion'])) {
            $this->createPricingRule($serviceTypes['care-l1-companion'], 599.00, 'Soprovozhdenie');
        }
        if (isset($serviceTypes['care-l1-good-visit'])) {
            $this->createPricingRule($serviceTypes['care-l1-good-visit'], 299.00, 'Dobryy vizit');
        }

        // Care L2 Services
        if (isset($serviceTypes['care-l2-med-remind'])) {
            $this->createPricingRule($serviceTypes['care-l2-med-remind'], 149.00, 'Naponimanija o lekarstvah');
        }
        if (isset($serviceTypes['care-l2-vitals-check'])) {
            $this->createPricingRule($serviceTypes['care-l2-vitals-check'], 299.00, 'Davlenie');
        }
        if (isset($serviceTypes['care-l2-rehab-assist'])) {
            $this->createPricingRule($serviceTypes['care-l2-rehab-assist'], 449.00, 'Reabilitaciya');
        }
        if (isset($serviceTypes['care-l2-telemed'])) {
            $this->createPricingRule($serviceTypes['care-l2-telemed'], 349.00, 'Telemedicina');
        }

        // Care L3 Services
        if (isset($serviceTypes['care-l3-sensors'])) {
            $this->createPricingRule($serviceTypes['care-l3-sensors'], 2990.00, 'Datchiki SOS');
        }

        // Eco Services
        if (isset($serviceTypes['eco-pickup-fridge'])) {
            $this->createPricingRule($serviceTypes['eco-pickup-fridge'], 599.00, 'Vyvoz holodilnika');
        }
        if (isset($serviceTypes['eco-pickup-mattress'])) {
            $this->createPricingRule($serviceTypes['eco-pickup-mattress'], 399.00, 'Vyvoz matrasa');
        }
        if (isset($serviceTypes['eco-pickup-furniture'])) {
            $this->createPricingRule($serviceTypes['eco-pickup-furniture'], 499.00, 'Vyvoz mebeli');
        }
        if (isset($serviceTypes['eco-pickup-electro'])) {
            $this->createPricingRule($serviceTypes['eco-pickup-electro'], 349.00, 'Elektro otkhody');
        }
        if (isset($serviceTypes['eco-clean-garage'])) {
            $this->createPricingRule($serviceTypes['eco-clean-garage'], 1490.00, 'Chistka garazha');
        }
        if (isset($serviceTypes['eco-clean-balcony'])) {
            $this->createPricingRule($serviceTypes['eco-clean-balcony'], 590.00, 'Chistka balkona');
        }
        if (isset($serviceTypes['eco-reuse-fretex'])) {
            $this->createPricingRule($serviceTypes['eco-reuse-fretex'], 299.00, 'Dostavka v Fretex');
        }
        if (isset($serviceTypes['eco-season-branches'])) {
            $this->createPricingRule($serviceTypes['eco-season-branches'], 499.00, 'Vetki');
        }

        // Tow Services
        if (isset($serviceTypes['tow-recovery-city'])) {
            $this->createPricingRule($serviceTypes['tow-recovery-city'], 1199.00, 'Evakuatsiya v gorode');
        }
        if (isset($serviceTypes['tow-jumpstart'])) {
            $this->createPricingRule($serviceTypes['tow-jumpstart'], 499.00, 'Prikurivanie');
        }
        if (isset($serviceTypes['tow-tire-change'])) {
            $this->createPricingRule($serviceTypes['tow-tire-change'], 599.00, 'Zamena kolesa');
        }
        if (isset($serviceTypes['tow-chains-install'])) {
            $this->createPricingRule($serviceTypes['tow-chains-install'], 499.00, 'Ustanovka tsepей');
        }
        if (isset($serviceTypes['tow-fuel-delivery'])) {
            $this->createPricingRule($serviceTypes['tow-fuel-delivery'], 499.00, 'Dostavka topliva');
        }

        // Rent Services
        if (isset($serviceTypes['rent-tools-tiller'])) {
            $this->createPricingRule($serviceTypes['rent-tools-tiller'], 249.00, 'Kultivator');
        }
        if (isset($serviceTypes['rent-tools-mower'])) {
            $this->createPricingRule($serviceTypes['rent-tools-mower'], 199.00, 'Gazokosilka');
        }
        if (isset($serviceTypes['rent-sport-skis'])) {
            $this->createPricingRule($serviceTypes['rent-sport-skis'], 149.00, 'Lyzhi');
        }
        if (isset($serviceTypes['rent-bike-bicycle'])) {
            $this->createPricingRule($serviceTypes['rent-bike-bicycle'], 199.00, 'Velosiped');
        }
        if (isset($serviceTypes['rent-baby-stroller'])) {
            $this->createPricingRule($serviceTypes['rent-baby-stroller'], 129.00, 'Kolyaska');
        }

        // Shuttle Services
        if (isset($serviceTypes['shuttle-ondemand-city'])) {
            $this->createPricingRule($serviceTypes['shuttle-ondemand-city'], 59.00, 'On-Demand gorod', true);
        }

        // Master Services
        if (isset($serviceTypes['master-assemble-furniture'])) {
            $this->createPricingRule($serviceTypes['master-assemble-furniture'], 699.00, 'Sborka mebeli');
        }
        if (isset($serviceTypes['master-install-washer'])) {
            $this->createPricingRule($serviceTypes['master-install-washer'], 599.00, 'Podklyuchenie stiralki');
        }
        if (isset($serviceTypes['master-fix-locks'])) {
            $this->createPricingRule($serviceTypes['master-fix-locks'], 399.00, 'Zamena zamka');
        }

        // Delivery Services - Narvik pricing rules
        $this->seedDeliveryPricingRules();
    }

    protected function seedDeliveryPricingRules(): void
    {
        $this->seedGrocery();
        $this->seedBulky();
        $this->seedFood();
    }

    protected function seedGrocery(): void
    {
        $zones = GeoZone::where('is_active', true)->get()->keyBy('slug');

        $rules = [
            [
                'service_type' => 'grocery',
                'geo_zone_slug' => 'narvik-sentrum',
                'base_fee' => 49,
                'per_km_fee' => 6,
            ],
            [
                'service_type' => 'grocery',
                'geo_zone_slug' => 'ankenes',
                'base_fee' => 59,
                'per_km_fee' => 7,
            ],
            [
                'service_type' => 'grocery',
                'geo_zone_slug' => 'fagernes',
                'base_fee' => 69,
                'per_km_fee' => 8,
            ],
            [
                'service_type' => 'grocery',
                'geo_zone_slug' => 'beisfjord',
                'base_fee' => 79,
                'per_km_fee' => 12,
            ],
            [
                'service_type' => 'grocery',
                'geo_zone_slug' => 'halogaland',
                'base_fee' => 69,
                'per_km_fee' => 11,
            ],
            [
                'service_type' => 'grocery',
                'geo_zone_slug' => 'forstadsone',
                'base_fee' => 89,
                'per_km_fee' => 14,
            ],
        ];

        foreach ($rules as $data) {
            $zone = $zones[$data['geo_zone_slug']] ?? null;

            if (! $zone) {
                continue;
            }

            PricingRule::updateOrCreate(
                [
                    'service_type' => $data['service_type'],
                    'geo_zone_id' => $zone->id,
                ],
                [
                    'name' => "Grocery delivery - {$zone->name}",
                    'description' => "Доставка продуктов в зону {$zone->name}",
                    'base_price' => $data['base_fee'], // Для обратной совместимости
                    'base_fee' => $data['base_fee'],
                    'per_km_fee' => $data['per_km_fee'],
                    'urgency_multiplier' => 1.2,
                    'night_multiplier' => 1.3,
                    'is_active' => true,
                ]
            );
        }
    }

    protected function seedBulky(): void
    {
        $zones = GeoZone::where('is_active', true)->get()->keyBy('slug');

        $rules = [
            [
                'service_type' => 'bulky',
                'geo_zone_slug' => 'narvik-sentrum',
                'base_fee' => 200,
                'per_km_fee' => 10,
                'per_m3_fee' => 80,
            ],
            [
                'service_type' => 'bulky',
                'geo_zone_slug' => 'ankenes',
                'base_fee' => 250,
                'per_km_fee' => 12,
                'per_m3_fee' => 90,
            ],
            [
                'service_type' => 'bulky',
                'geo_zone_slug' => 'fagernes',
                'base_fee' => 280,
                'per_km_fee' => 14,
                'per_m3_fee' => 100,
            ],
            [
                'service_type' => 'bulky',
                'geo_zone_slug' => 'beisfjord',
                'base_fee' => 300,
                'per_km_fee' => 15,
                'per_m3_fee' => 110,
            ],
            [
                'service_type' => 'bulky',
                'geo_zone_slug' => 'halogaland',
                'base_fee' => 280,
                'per_km_fee' => 13,
                'per_m3_fee' => 100,
            ],
            [
                'service_type' => 'bulky',
                'geo_zone_slug' => 'forstadsone',
                'base_fee' => 320,
                'per_km_fee' => 16,
                'per_m3_fee' => 120,
            ],
        ];

        foreach ($rules as $data) {
            $zone = $zones[$data['geo_zone_slug']] ?? null;

            if (! $zone) {
                continue;
            }

            PricingRule::updateOrCreate(
                [
                    'service_type' => $data['service_type'],
                    'geo_zone_id' => $zone->id,
                ],
                [
                    'name' => "Bulky delivery - {$zone->name}",
                    'description' => "Доставка крупногабарита в зону {$zone->name}",
                    'base_price' => $data['base_fee'], // Для обратной совместимости
                    'base_fee' => $data['base_fee'],
                    'per_km_fee' => $data['per_km_fee'],
                    'per_m3_fee' => $data['per_m3_fee'],
                    'urgency_multiplier' => 1.3,
                    'night_multiplier' => 1.4,
                    'is_active' => true,
                ]
            );
        }
    }

    protected function seedFood(): void
    {
        $zones = GeoZone::where('is_active', true)->get()->keyBy('slug');

        $rules = [
            [
                'service_type' => 'food',
                'geo_zone_slug' => 'narvik-sentrum',
                'base_fee' => 39,
                'per_km_fee' => 5,
            ],
            [
                'service_type' => 'food',
                'geo_zone_slug' => 'ankenes',
                'base_fee' => 49,
                'per_km_fee' => 6,
            ],
            [
                'service_type' => 'food',
                'geo_zone_slug' => 'fagernes',
                'base_fee' => 55,
                'per_km_fee' => 7,
            ],
            [
                'service_type' => 'food',
                'geo_zone_slug' => 'beisfjord',
                'base_fee' => 63,
                'per_km_fee' => 10,
            ],
            [
                'service_type' => 'food',
                'geo_zone_slug' => 'halogaland',
                'base_fee' => 55,
                'per_km_fee' => 9,
            ],
            [
                'service_type' => 'food',
                'geo_zone_slug' => 'forstadsone',
                'base_fee' => 71,
                'per_km_fee' => 11,
            ],
        ];

        foreach ($rules as $data) {
            $zone = $zones[$data['geo_zone_slug']] ?? null;

            if (! $zone) {
                continue;
            }

            PricingRule::updateOrCreate(
                [
                    'service_type' => $data['service_type'],
                    'geo_zone_id' => $zone->id,
                ],
                [
                    'name' => "Food delivery - {$zone->name}",
                    'description' => "Доставка готовой еды в зону {$zone->name}",
                    'base_price' => $data['base_fee'], // Для обратной совместимости
                    'base_fee' => $data['base_fee'],
                    'per_km_fee' => $data['per_km_fee'],
                    'urgency_multiplier' => 1.15,
                    'night_multiplier' => 1.25,
                    'is_active' => true,
                ]
            );
        }

        $this->command?->info('Seeded delivery pricing rules for Narvik zones.');
    }

    private function createPricingRule($serviceType, $basePrice, $name, $perKm = false): void
    {
        $slug = Str::slug($name) ?: Str::uuid()->toString();

        PricingRule::updateOrCreate(
            ['slug' => $slug],
            [
                'service_type_id' => $serviceType->id,
                'name' => $name,
                'description' => 'Osnovnaya cena uslugi',
                'base_price' => $basePrice,
                'currency' => 'NOK',
                'pricing_model' => [
                    'type' => $perKm ? 'distance_based' : 'fixed',
                    'per_km' => $perKm ? 12.00 : null,
                ],
                'conditions' => null,
                'modifiers' => [
                    'evening_surcharge' => 0.20,
                    'winter_surcharge' => 0.30,
                    'weekend_surcharge' => 0.15,
                ],
                'is_active' => true,
            ]
        );
    }
}
