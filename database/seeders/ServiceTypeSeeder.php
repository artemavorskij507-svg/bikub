<?php

namespace Database\Seeders;

use App\Models\ServiceType;
use Illuminate\Database\Seeder;

class ServiceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCareServices();
        $this->seedEcoServices();
        $this->seedTowServices();
        $this->seedRentServices();
        $this->seedShuttleServices();
        $this->seedMasterServices();
    }

    private function seedCareServices(): void
    {
        $services = [
            ['Доставка лекарств', 'care-l1-med-delivery', 'Забираем в аптеке, проверяем ID/рецепт', 40],
            ['Поручения в городе', 'care-l1-errands', 'Мелкие закупки/почта/оплата счетов', 60],
            ['Занос на этаж/дрова', 'care-l1-stairs-firewood', 'Подъём грузов/дров', 45],
            ['Лампочка/батарейки', 'care-l1-light-fix', 'Мелкий быт без электромонтажа', 25],
            ['Сопровождение', 'care-l1-companion', 'Транспорт/ожидание/помощь', 90],
            ['Добрый визит', 'care-l1-good-visit', 'Час общения/профилактика одиночества', 60],
            ['Напоминания о лекарствах', 'care-l2-med-remind', 'Без манипуляций, контроль графика', 15],
            ['Давление/сахар', 'care-l2-vitals-check', 'Сбор показаний, передача медслужбе', 25],
            ['Помощь в реабилитации', 'care-l2-rehab-assist', 'Простые упражнения/сопровождение', 50],
            ['Телемедицина-связь', 'care-l2-telemed', 'Вызов врача через планшет', 30],
            ['Безбарьерные доработки', 'care-l3-safety-home', 'Поручни/антискользящие зоны'],
            ['Датчики и SOS', 'care-l3-sensors', 'Пожар/газ/падение/SOS', 90],
            ['Уборка у входа', 'care-l3-snow-entry', 'Расчистка ступеней/посыпка', 30],
        ];

        foreach ($services as $index => $service) {
            ServiceType::updateOrCreate(
                ['slug' => $service[1]],
                [
                    'name' => $service[0],
                    'description' => $service[2],
                    'category' => 'care',
                    'icon' => 'heart',
                    'estimated_duration_minutes' => $service[3] ?? null,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }

    private function seedEcoServices(): void
    {
        $services = [
            ['Вывоз холодильника', 'eco-pickup-fridge', 'Вынос, транспорт, сдача', 60],
            ['Вывоз матраса', 'eco-pickup-mattress', 'Упаковка, вынос, сдача', 45],
            ['Вывоз мебели', 'eco-pickup-furniture', 'Разбор/вынос/сдача', 90],
            ['Электро лом', 'eco-pickup-electro', 'Упаковка, сдача', 45],
            ['Чистка гаража', 'eco-clean-garage', 'Разбор/сортировка/вывоз', 120],
            ['Чистка подвала', 'eco-clean-basement', 'Сортировка/вывоз', 120],
            ['Чистка балкона', 'eco-clean-balcony', 'Коробки/мебель', 60],
            ['После ремонта', 'eco-move-reno', 'Мешки/меш-контейнер', 90],
            ['Картон от офисов', 'eco-office-cardboard', 'Регулярный забор'],
            ['Пластик от офисов', 'eco-office-plastic', 'Регулярный забор'],
            ['Доставка в Fretex', 'eco-reuse-fretex', 'Вторичная жизнь'],
            ['Сортировка на месте', 'eco-sorting-on-site', 'Обучение+разбор', 90],
            ['Ветки/листья', 'eco-season-branches', 'Погрузка/вывоз', 60],
            ['Вывоз снега', 'eco-season-snow', 'Зоны у входа', 45],
            ['Тяжёлый подъём', 'eco-heavy-two-man', 'Безопасный подъём', 90],
        ];

        foreach ($services as $index => $service) {
            ServiceType::updateOrCreate(
                ['slug' => $service[1]],
                [
                    'name' => $service[0],
                    'description' => $service[2],
                    'category' => 'eco',
                    'icon' => 'leaf',
                    'estimated_duration_minutes' => $service[3] ?? null,
                    'is_active' => true,
                    'sort_order' => $index + 14,
                ]
            );
        }
    }

    private function seedTowServices(): void
    {
        $services = [
            ['Эвакуация в городе', 'tow-recovery-city', 'Погрузка/перевозка', 60],
            ['Эвакуация по E6/E10', 'tow-recovery-road', 'Км-тариф + база', 120],
            ['Вытягивание из снега', 'tow-pullout-snow', 'Лебёдка/страпы', 45],
            ['Прикуривание', 'tow-jumpstart', 'Пуск/проверка', 30],
            ['Замена колеса', 'tow-tire-change', 'Домкрат/момент', 30],
            ['Установка цепей', 'tow-chains-install', 'Пара колёс', 20],
            ['Доставка топлива', 'tow-fuel-delivery', 'Топливо + доставка', 45],
            ['Транспорт до СТО', 'tow-to-service', 'Партнёрские СТО', 60],
        ];

        foreach ($services as $index => $service) {
            ServiceType::updateOrCreate(
                ['slug' => $service[1]],
                [
                    'name' => $service[0],
                    'description' => $service[2],
                    'category' => 'tow',
                    'icon' => 'tow-truck',
                    'estimated_duration_minutes' => $service[3] ?? null,
                    'is_active' => true,
                    'sort_order' => $index + 29,
                ]
            );
        }
    }

    private function seedRentServices(): void
    {
        $services = [
            ['Электрокультиватор', 'rent-tools-tiller', 'Аренда на сутки', 1],
            ['Газонокосилка', 'rent-tools-mower', 'Аренда на сутки', 1],
            ['Перфоратор', 'rent-tools-perf', 'Аренда на сутки', 1],
            ['Пылесос строительный', 'rent-clean-vacuum', 'Аренда на сутки', 1],
            ['Пароочиститель', 'rent-clean-steam', 'Аренда на сутки', 1],
            ['Лыжи комплект', 'rent-sport-skis', 'Аренда на сутки', 1],
            ['Сноуборд комплект', 'rent-sport-snowboard', 'Аренда на сутки', 1],
            ['Шипы на ботинки', 'rent-sport-spikes', 'Пара', 1],
            ['Велосипед', 'rent-bike-bicycle', 'Город/MTB', 1],
            ['Велоприцеп', 'rent-bike-trailer', 'Дет/карго', 1],
            ['Коляска', 'rent-baby-stroller', 'Аренда на сутки', 1],
            ['Домкрат', 'rent-auto-jack', 'Автомобильный', 1],
            ['Зарядка АКБ', 'rent-auto-charger', 'Автомобильная батарея', 1],
        ];

        foreach ($services as $index => $service) {
            ServiceType::updateOrCreate(
                ['slug' => $service[1]],
                [
                    'name' => $service[0],
                    'description' => $service[2],
                    'category' => 'rent',
                    'icon' => 'package',
                    'estimated_duration_minutes' => $service[3] ?? null,
                    'is_active' => true,
                    'sort_order' => $index + 38,
                ]
            );
        }
    }

    private function seedShuttleServices(): void
    {
        $services = [
            ['Маршрутная линия №1', 'shuttle-route-line1', 'Фикс-остановки'],
            ['Маршрутная линия №2', 'shuttle-route-line2', 'Расширение зоны'],
            ['On-Demand город', 'shuttle-ondemand-city', 'Алго-маршрутизация'],
            ['On-Demand регион', 'shuttle-ondemand-region', '+60 км'],
            ['Месячный пасс', 'shuttle-pass-month', 'Корп/личный'],
        ];

        foreach ($services as $index => $service) {
            ServiceType::updateOrCreate(
                ['slug' => $service[1]],
                [
                    'name' => $service[0],
                    'description' => $service[2],
                    'category' => 'shuttle',
                    'icon' => 'bus',
                    'is_active' => true,
                    'sort_order' => $index + 52,
                ]
            );
        }
    }

    private function seedMasterServices(): void
    {
        $services = [
            ['Сборка мебели', 'master-assemble-furniture', 'Шкаф/кровать/стол', 120],
            ['Подключение стиралки', 'master-install-washer', 'Шланги/уровень', 60],
            ['Монтаж душкабины', 'master-install-shower', 'Сборка/герметизация', 180],
            ['Замена замка', 'master-fix-locks', 'Дверь/комплект', 45],
            ['Лампочки/светильники', 'master-fix-lightsimple', 'Замена/крепёж', 30],
            ['Зима готова', 'master-seal-winter', 'Уплотнители/щели', 90],
            ['Помочь бабушке', 'master-check-safety', 'Поручни/коврики', 120],
            ['Заехал и живи', 'master-combo-movein', 'Сборка+подключение', 240],
        ];

        foreach ($services as $index => $service) {
            ServiceType::updateOrCreate(
                ['slug' => $service[1]],
                [
                    'name' => $service[0],
                    'description' => $service[2],
                    'category' => 'master',
                    'icon' => 'hammer',
                    'estimated_duration_minutes' => $service[3] ?? null,
                    'is_active' => true,
                    'sort_order' => $index + 57,
                ]
            );
        }
    }
}
