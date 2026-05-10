<?php

namespace Database\Seeders;

use App\Models\HandymanMaterialsEntry;
use App\Models\Moving\ExecutorProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HandymanMaterialsSeeder extends Seeder
{
    public function run(): void
    {
        $materials = [
            [
                'name' => 'Дюбель + шуруп (универсальный, 6×40)',
                'category' => 'Крепёж',
                'unit' => 'шт',
                'default_price' => 5,
                'notes' => 'Стандартный набор для большинства стен, используется в карнизах и полках',
            ],
            [
                'name' => 'Анкерный болт (10×80)',
                'category' => 'Крепёж',
                'unit' => 'шт',
                'default_price' => 15,
                'notes' => 'Для тяжёлых полок, шкафов и крепления в бетон/кирпич',
            ],
            [
                'name' => 'Сантехнический герметик',
                'category' => 'Сантехника',
                'unit' => 'тюбик',
                'default_price' => 79,
                'notes' => 'Для уплотнения соединений вокруг раковин, душевых кабин и стиральных машин',
            ],
            [
                'name' => 'Тефлоновая лента (FUM)',
                'category' => 'Сантехника',
                'unit' => 'рулон',
                'default_price' => 39,
                'notes' => 'Используется при подключении стиралок/посудомоек и перекрутке соединений',
            ],
            [
                'name' => 'Кабель-канал белый 20×10',
                'category' => 'Электрика',
                'unit' => 'м',
                'default_price' => 49,
                'notes' => 'Для аккуратной прокладки проводов по стене',
            ],
            [
                'name' => 'Клеммы WAGO',
                'category' => 'Электрика',
                'unit' => 'шт',
                'default_price' => 10,
                'notes' => 'Быстрые соединители для электромонтажных работ',
            ],
            [
                'name' => 'Регулируемые ножки для мебели',
                'category' => 'Мебель',
                'unit' => 'шт',
                'default_price' => 25,
                'notes' => 'Используются при установке кухонь и шкафов на неровный пол',
            ],
            [
                'name' => 'Комплект для подвеса шкафчика',
                'category' => 'Мебель',
                'unit' => 'комплект',
                'default_price' => 129,
                'notes' => 'Комплект крепежа для навесных шкафчиков, особенно на кухнях',
            ],
            [
                'name' => 'Перчатки защитные',
                'category' => 'Расходники',
                'unit' => 'пара',
                'default_price' => 29,
                'notes' => 'Средства защиты при работах',
            ],
            [
                'name' => 'Плёнка защитная и малярный скотч',
                'category' => 'Расходники',
                'unit' => 'набор',
                'default_price' => 59,
                'notes' => 'Для защиты пола и мебели при мелком ремонте',
            ],
        ];

        $catalogOwner = ExecutorProfile::whereHas('user', fn ($q) => $q->where('email', 'dmytro.handyman@bikube.no'))->first()
            ?? ExecutorProfile::first();

        if (! $catalogOwner) {
            $this->command?->warn('Не найден исполнитель для назначения материалов. Сначала запустите HandymanTeamSeeder.');

            return;
        }

        foreach ($materials as $item) {
            $priceMinor = (int) round($item['default_price'] * 100);

            HandymanMaterialsEntry::updateOrCreate(
                [
                    'executor_profile_id' => $catalogOwner->id,
                    'description' => $item['name'],
                    'order_id' => null,
                    'repair_project_id' => null,
                ],
                [
                    'quantity' => 1,
                    'unit' => $item['unit'],
                    'unit_price_minor' => $priceMinor,
                    'total_price_minor' => $priceMinor,
                    'meta' => [
                        'category' => $item['category'],
                        'notes' => $item['notes'],
                        'region' => 'Narvik +60km',
                        'is_catalog_item' => true,
                        'slug' => Str::slug($item['name']),
                    ],
                ]
            );
        }
    }
}
