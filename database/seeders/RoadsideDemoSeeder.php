<?php

namespace Database\Seeders;

use App\Models\GeoZone;
use App\Models\Partner;
use App\Models\RoadHelperProfile;
use App\Models\RoadsideEmergency;
use App\Models\RoadsidePreset;
use App\Models\User;
use App\Models\VehicleInspectionPreset;
use App\Models\VehicleInspectionRequest;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoadsideDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Заполнение Roadside & Tow демо-данными...');

        // 0. Создать типы услуг Roadside (если их ещё нет)
        $this->call(RoadsideServiceTypesSeeder::class);

        // 1. Создать партнёров (эвакуаторные компании)
        $partners = $this->createPartners();
        $this->command->info("✅ Создано/обновлено партнёров: {$partners}");

        // 2. Создать помощников
        $helpers = $this->createHelpers();
        $this->command->info("✅ Создано/обновлено помощников: {$helpers}");

        // 3. Создать пресеты Roadside
        $roadsidePresets = $this->createRoadsidePresets();
        $this->command->info("✅ Создано/обновлено пресетов Roadside: {$roadsidePresets}");

        // 4. Создать пресеты осмотра
        $inspectionPresets = $this->createInspectionPresets();
        $this->command->info("✅ Создано/обновлено пресетов осмотра: {$inspectionPresets}");

        // 5. (Опционально) Создать демо-заявки
        $emergencies = $this->createDemoEmergencies($partners, $helpers);
        $this->command->info("✅ Создано/обновлено экстренных вызовов: {$emergencies}");

        $inspections = $this->createDemoInspections($helpers);
        $this->command->info("✅ Создано/обновлено заявок на осмотр: {$inspections}");

        $this->command->info('✨ Демо-данные Roadside & Tow успешно заполнены!');
    }

    /**
     * Создать партнёров (эвакуаторные компании).
     */
    protected function createPartners(): int
    {
        $count = 0;

        $partnerData = [
            [
                'name' => 'Nordic Tow Service AS',
                'type' => Partner::TYPE_TOWING_SERVICE,
                'slug' => 'nordic-tow-service',
                'phone' => '+47 22 11 22 33',
                'email' => 'info@nordictow.no',
                'capabilities' => ['towing' => 'Эвакуация', 'winching' => 'Вытаскивание'],
                'priority' => 10,
                'metadata' => ['sla_minutes' => 45],
            ],
            [
                'name' => 'Arctic Roadside Assistance Ltd',
                'type' => 'roadside_mobile',
                'slug' => 'arctic-roadside',
                'phone' => '+47 22 44 55 66',
                'email' => 'help@arcticroad.no',
                'capabilities' => [
                    'jump_start' => 'Прикуривание',
                    'wheel_change' => 'Замена колеса',
                    'fuel_delivery' => 'Подвоз топлива',
                ],
                'priority' => 20,
                'metadata' => ['sla_minutes' => 30],
            ],
            [
                'name' => 'Fjord Towing AS',
                'type' => Partner::TYPE_TOWING_SERVICE,
                'slug' => 'fjord-towing',
                'phone' => '+47 22 77 88 99',
                'email' => 'contact@fjordtowing.no',
                'capabilities' => ['towing' => 'Эвакуация', 'winching' => 'Вытаскивание'],
                'priority' => 15,
                'metadata' => ['sla_minutes' => 60],
            ],
            [
                'name' => 'Oslo Mobile Repair AB',
                'type' => 'roadside_mobile',
                'slug' => 'oslo-mobile-repair',
                'phone' => '+47 22 33 44 55',
                'email' => 'service@oslomobile.no',
                'capabilities' => [
                    'jump_start' => 'Прикуривание',
                    'wheel_change' => 'Замена колеса',
                    'basic_diagnostics' => 'Диагностика',
                ],
                'priority' => 25,
                'metadata' => ['sla_minutes' => 40],
            ],
            [
                'name' => 'Bergen Auto Service AS',
                'type' => Partner::TYPE_SERVICE_STATION,
                'slug' => 'bergen-auto-service',
                'phone' => '+47 55 12 34 56',
                'email' => 'info@bergenauto.no',
                'capabilities' => [
                    'diagnostics' => 'Диагностика',
                    'towing' => 'Эвакуация',
                ],
                'priority' => 30,
                'metadata' => ['sla_minutes' => 90],
            ],
        ];

        $zone = GeoZone::first();

        foreach ($partnerData as $data) {
            Partner::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, [
                    'geo_zone_id' => $zone?->id,
                    'is_active' => true,
                    'active' => true,
                    'is_available' => true,
                    'rating_avg' => fake()->randomFloat(1, 4.2, 4.9),
                    'on_time_rate' => fake()->randomFloat(2, 0.88, 0.97),
                    'emergency_price_base' => fake()->randomFloat(2, 600, 1800),
                    'emergency_price_per_km' => fake()->randomFloat(2, 20, 45),
                ])
            );
            $count++;
        }

        return $count;
    }

    /**
     * Создать помощников.
     */
    protected function createHelpers(): int
    {
        $count = 0;

        // Получить или создать роль roadside_assist
        $role = Role::firstOrCreate(['name' => 'roadside_assist']);

        // Найти существующих пользователей с ролью или создать новых
        $existingUsers = User::role(['roadside_assist', 'executor', 'eco_executor'])->get();

        if ($existingUsers->isEmpty()) {
            // Создать 5 новых пользователей с ролью roadside_assist
            for ($i = 1; $i <= 5; $i++) {
                $user = User::factory()->create([
                    'name' => fake()->name(),
                    'email' => "roadside{$i}@demo.test",
                ]);
                $user->assignRole($role);
                $existingUsers->push($user);
            }
        }

        // Создать профили для помощников
        $helperData = [
            [
                'vehicle_type' => 'van',
                'vehicle_model' => 'Ford Transit',
                'skills' => ['jump_start', 'tire_change', 'fuel_delivery'],
                'current_status' => 'available',
            ],
            [
                'vehicle_type' => 'pickup',
                'vehicle_model' => 'Toyota Hilux',
                'skills' => ['jump_start', 'tire_change', 'basic_diagnostics'],
                'current_status' => 'available',
            ],
            [
                'vehicle_type' => 'truck',
                'vehicle_model' => 'Mercedes Sprinter',
                'skills' => ['tire_change', 'fuel_delivery', 'towing'],
                'current_status' => 'on_duty',
            ],
            [
                'vehicle_type' => 'suv',
                'vehicle_model' => 'Volkswagen Crafter',
                'skills' => ['jump_start', 'basic_diagnostics'],
                'current_status' => 'available',
            ],
            [
                'vehicle_type' => 'van',
                'vehicle_model' => 'Ford Transit',
                'skills' => ['jump_start', 'tire_change', 'fuel_delivery', 'basic_diagnostics'],
                'current_status' => 'available',
            ],
        ];

        foreach ($existingUsers->take(5) as $index => $user) {
            $helperInfo = $helperData[$index] ?? $helperData[0];

            RoadHelperProfile::updateOrCreate(
                ['user_id' => $user->id],
                array_merge($helperInfo, [
                    'vehicle_number' => fake()->regexify('[A-Z]{2}[0-9]{5}'),
                    'equipment' => ['jumper_cables', 'tire_repair_kit', 'basic_tools'],
                    'location_lat' => fake()->latitude(59.5, 60.0),
                    'location_lng' => fake()->longitude(10.5, 11.0),
                    'metadata' => [
                        'experience_years' => fake()->numberBetween(1, 8),
                        'rating' => fake()->randomFloat(1, 4.0, 5.0),
                        'completed_jobs' => fake()->numberBetween(20, 300),
                    ],
                ])
            );
            $count++;
        }

        return $count;
    }

    /**
     * Создать пресеты Roadside.
     */
    protected function createRoadsidePresets(): int
    {
        $count = 0;

        $presets = [
            [
                'code' => 'jump_start',
                'label' => 'Прикурить авто',
                'description' => 'Запуск двигуна через прикуривание від іншого автомобіля',
                'service_type' => 'roadside_assistance',
                'base_price' => 500,
                'requires_partner' => false,
                'sort_order' => 1,
            ],
            [
                'code' => 'tire_change',
                'label' => 'Замена колеса',
                'description' => 'Заміна проколотого колеса на запаске',
                'service_type' => 'roadside_assistance',
                'base_price' => 600,
                'requires_partner' => false,
                'sort_order' => 2,
            ],
            [
                'code' => 'fuel_delivery',
                'label' => 'Привезти топливо',
                'description' => 'Доставка палива до місця зупинки',
                'service_type' => 'roadside_assistance',
                'base_price' => 800,
                'requires_partner' => false,
                'sort_order' => 3,
            ],
            [
                'code' => 'basic_diagnostics',
                'label' => 'Лёгкая диагностика',
                'description' => 'Базова діагностика проблем з автомобілем',
                'service_type' => 'roadside_assistance',
                'base_price' => 700,
                'requires_partner' => false,
                'sort_order' => 4,
            ],
            [
                'code' => 'towing',
                'label' => 'Эвакуация',
                'description' => 'Евакуація автомобіля до СТО або іншого місця',
                'service_type' => 'vehicle_transport',
                'base_price' => 1500,
                'requires_partner' => true,
                'sort_order' => 5,
            ],
            [
                'code' => 'winching',
                'label' => 'Вытаскивание',
                'description' => 'Витягування автомобіля зі снігу, грязі або іншої перешкоди',
                'service_type' => 'roadside_assistance',
                'base_price' => 1200,
                'requires_partner' => true,
                'sort_order' => 6,
            ],
            [
                'code' => 'lockout',
                'label' => 'Открытие замка',
                'description' => 'Відкриття автомобіля при закритих ключах всередині',
                'service_type' => 'roadside_assistance',
                'base_price' => 900,
                'requires_partner' => false,
                'sort_order' => 7,
            ],
        ];

        foreach ($presets as $preset) {
            RoadsidePreset::updateOrCreate(
                ['code' => $preset['code']],
                array_merge($preset, [
                    'is_active' => true,
                    'metadata' => [
                        'estimated_duration_minutes' => fake()->numberBetween(15, 60),
                    ],
                ])
            );
            $count++;
        }

        return $count;
    }

    /**
     * Создать пресеты осмотра.
     */
    protected function createInspectionPresets(): int
    {
        $count = 0;

        $presets = [
            [
                'title' => 'Предпокупочная проверка',
                'slug' => 'predpokupochnaya-proverka',
                'description' => 'Повна перевірка автомобіля перед покупкою: технічний стан, історія, документи',
                'price' => 2500,
                'checklist' => [
                    'Зовнішній огляд кузова',
                    'Перевірка двигуна',
                    'Перевірка ходової частини',
                    'Перевірка гальм',
                    'Перевірка електроніки',
                    'Перевірка документів',
                    'Перевірка історії автомобіля',
                ],
                'sort_order' => 1,
            ],
            [
                'title' => 'Базовый осмотр ходовой',
                'slug' => 'bazovyy-osmotr-hodovoy',
                'description' => 'Базова перевірка ходової частини: підвіска, гальма, шини',
                'price' => 1200,
                'checklist' => [
                    'Перевірка амортизаторів',
                    'Перевірка пружин',
                    'Перевірка сайлентблоків',
                    'Перевірка гальмових колодок',
                    'Перевірка гальмових дисків',
                    'Перевірка стану шин',
                ],
                'sort_order' => 2,
            ],
            [
                'title' => 'Проверка при продаже',
                'slug' => 'proverka-pri-prodazhe',
                'description' => 'Огляд автомобіля перед продажем для оцінки ринкової вартості',
                'price' => 1800,
                'checklist' => [
                    'Оцінка зовнішнього стану',
                    'Перевірка технічного стану',
                    'Оцінка вартості',
                    'Фотофіксація',
                    'Складання звіту',
                ],
                'sort_order' => 3,
            ],
            [
                'title' => 'Полная диагностика',
                'slug' => 'polnaya-diagnostika',
                'description' => 'Повна діагностика всіх систем автомобіля',
                'price' => 3500,
                'checklist' => [
                    'Діагностика двигуна',
                    'Діагностика коробки передач',
                    'Діагностика електроніки',
                    'Перевірка системи охолодження',
                    'Перевірка системи живлення',
                    'Перевірка вихлопної системи',
                    'Комп\'ютерна діагностика',
                ],
                'sort_order' => 4,
            ],
        ];

        foreach ($presets as $preset) {
            VehicleInspectionPreset::updateOrCreate(
                ['slug' => $preset['slug']],
                array_merge($preset, [
                    'is_active' => true,
                    'metadata' => [
                        'estimated_duration_minutes' => fake()->numberBetween(30, 120),
                        'inspection_type' => 'standard',
                    ],
                ])
            );
            $count++;
        }

        return $count;
    }

    /**
     * Создать демо-заявки (экстренные вызовы).
     */
    protected function createDemoEmergencies(int $partnersCount, int $helpersCount): int
    {
        $count = 0;

        $customers = User::whereDoesntHave('roles', function ($q) {
            $q->whereIn('name', ['admin', 'operator', 'dispatcher', 'roadside_assist', 'executor']);
        })->limit(10)->get();

        if ($customers->isEmpty()) {
            // Создать демо-клиентов
            $customers = User::factory()->count(5)->create();
        }

        $partners = Partner::roadside()->limit($partnersCount)->get();
        $helpers = RoadHelperProfile::limit($helpersCount)->get();
        $statuses = ['new', 'assigned', 'in_progress', 'completed'];

        for ($i = 0; $i < 5; $i++) {
            $status = fake()->randomElement($statuses);
            $customer = $customers->random();
            $partner = $partners->isNotEmpty() ? $partners->random() : null;
            $helper = $helpers->isNotEmpty() && in_array($status, ['assigned', 'in_progress', 'completed'])
                ? $helpers->random()
                : null;

            $incidentTypes = ['jump_start', 'fuel', 'flat_tire', 'locked_keys', 'engine_no_start', 'tow_needed'];

            RoadsideEmergency::updateOrCreate(
                [
                    'customer_id' => $customer->id,
                    'created_at' => now()->subDays(fake()->numberBetween(0, 7)),
                ],
                [
                    'road_helper_id' => $helper?->id,
                    'resolved_by_partner_id' => $partner?->id,
                    'incident_type' => fake()->randomElement($incidentTypes),
                    'incident_description' => fake()->sentence(),
                    'lat' => fake()->latitude(59.5, 60.0),
                    'lng' => fake()->longitude(10.5, 11.0),
                    'status' => $status,
                    'metadata' => [
                        'created_via' => 'demo_seeder',
                    ],
                ]
            );
            $count++;
        }

        return $count;
    }

    /**
     * Создать демо-заявки на осмотр.
     */
    protected function createDemoInspections(int $helpersCount): int
    {
        $count = 0;

        $customers = User::whereDoesntHave('roles', function ($q) {
            $q->whereIn('name', ['admin', 'operator', 'dispatcher', 'roadside_assist', 'executor']);
        })->limit(10)->get();

        if ($customers->isEmpty()) {
            $customers = User::factory()->count(3)->create();
        }

        $presets = VehicleInspectionPreset::all();
        $helpers = RoadHelperProfile::limit($helpersCount)->get();
        $statuses = ['pending', 'assigned', 'in_progress', 'completed'];

        for ($i = 0; $i < 3; $i++) {
            $status = fake()->randomElement($statuses);
            $customer = $customers->random();
            $preset = $presets->random();
            $helper = $helpers->isNotEmpty() && in_array($status, ['assigned', 'in_progress', 'completed'])
                ? $helpers->random()
                : null;

            VehicleInspectionRequest::updateOrCreate(
                [
                    'customer_id' => $customer->id,
                    'preset_id' => $preset->id,
                    'created_at' => now()->subDays(fake()->numberBetween(0, 5)),
                ],
                [
                    'assigned_helper_id' => $helper?->id,
                    'seller_name' => fake()->name(),
                    'seller_phone' => '+47 '.fake()->numerify('#### ####'),
                    'vehicle_make' => fake()->randomElement(['Toyota', 'Volkswagen', 'BMW', 'Mercedes', 'Audi']),
                    'vehicle_model' => fake()->word(),
                    'vehicle_year' => fake()->numberBetween(2015, 2024),
                    'vin_code' => fake()->regexify('[A-Z0-9]{17}'),
                    'address' => fake()->address(),
                    'requested_time' => now()->addDays(fake()->numberBetween(1, 7)),
                    'status' => $status,
                    'metadata' => [
                        'created_via' => 'demo_seeder',
                    ],
                ]
            );
            $count++;
        }

        return $count;
    }
}
