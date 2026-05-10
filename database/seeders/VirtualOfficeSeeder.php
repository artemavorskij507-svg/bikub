<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VirtualOffice\Category;
use App\Models\VirtualOffice\OfficeZone;
use App\Models\VirtualOffice\Agent;

class VirtualOfficeSeeder extends Seeder
{
    /**
     * Запуск сидера
     */
    public function run(): void
    {
        // Очистить таблицы
        Agent::truncate();
        Category::truncate();
        OfficeZone::truncate();

        // Создать категории
        $categories = $this->createCategories();

        // Создать офисные зоны
        $zones = $this->createOfficeZones();

        // Создать агентов
        $this->createAgents($categories, $zones);
    }

    /**
     * Создать категории
     */
    private function createCategories(): array
    {
        $categoriesData = [
            [
                'name' => 'Engineering',
                'slug' => 'engineering',
                'description' => 'Инженеры и разработчики',
                'color' => '#3B82F6',
                'icon' => '⚙️',
                'sector_x_min' => 0,
                'sector_x_max' => 200,
                'sector_y_min' => 0,
                'sector_y_max' => 200,
            ],
            [
                'name' => 'Design',
                'slug' => 'design',
                'description' => 'Дизайнеры и UX/UI специалисты',
                'color' => '#EC4899',
                'icon' => '🎨',
                'sector_x_min' => 200,
                'sector_x_max' => 400,
                'sector_y_min' => 0,
                'sector_y_max' => 200,
            ],
            [
                'name' => 'Marketing',
                'slug' => 'marketing',
                'description' => 'Маркетологи и контент-мейкеры',
                'color' => '#10B981',
                'icon' => '📢',
                'sector_x_min' => 400,
                'sector_x_max' => 600,
                'sector_y_min' => 0,
                'sector_y_max' => 200,
            ],
            [
                'name' => 'Sales',
                'slug' => 'sales',
                'description' => 'Продавцы и бизнес-разработчики',
                'color' => '#F59E0B',
                'icon' => '💼',
                'sector_x_min' => 600,
                'sector_x_max' => 800,
                'sector_y_min' => 0,
                'sector_y_max' => 200,
            ],
            [
                'name' => 'Support',
                'slug' => 'support',
                'description' => 'Служба поддержки и операции',
                'color' => '#8B5CF6',
                'icon' => '🛟',
                'sector_x_min' => 0,
                'sector_x_max' => 200,
                'sector_y_min' => 200,
                'sector_y_max' => 400,
            ],
            [
                'name' => 'Analytics',
                'slug' => 'analytics',
                'description' => 'Аналитики и data scientists',
                'color' => '#06B6D4',
                'icon' => '📊',
                'sector_x_min' => 200,
                'sector_x_max' => 400,
                'sector_y_min' => 200,
                'sector_y_max' => 400,
            ],
            [
                'name' => 'Management',
                'slug' => 'management',
                'description' => 'Менеджеры и руководители',
                'color' => '#EF4444',
                'icon' => '👔',
                'sector_x_min' => 400,
                'sector_x_max' => 600,
                'sector_y_min' => 200,
                'sector_y_max' => 400,
            ],
            [
                'name' => 'Creative',
                'slug' => 'creative',
                'description' => 'Креативные специалисты',
                'color' => '#F97316',
                'icon' => '✨',
                'sector_x_min' => 600,
                'sector_x_max' => 800,
                'sector_y_min' => 200,
                'sector_y_max' => 400,
            ],
        ];

        $categories = [];
        foreach ($categoriesData as $data) {
            $categories[$data['slug']] = Category::create($data);
        }

        return $categories;
    }

    /**
     * Создать офисные зоны
     */
    private function createOfficeZones(): array
    {
        $zonesData = [
            [
                'name' => 'Рабочая зона',
                'slug' => 'workspace',
                'icon' => '💻',
                'color' => '#E0F2FE',
                'x_min' => 50,
                'x_max' => 750,
                'y_min' => 50,
                'y_max' => 350,
                'capacity' => 100,
                'amenities' => ['Wi-Fi', 'Розетки', 'Мониторы', 'Столы'],
            ],
            [
                'name' => 'Переговорная',
                'slug' => 'meeting-room',
                'icon' => '🤝',
                'color' => '#FEF3C7',
                'x_min' => 50,
                'x_max' => 250,
                'y_min' => 400,
                'y_max' => 550,
                'capacity' => 20,
                'amenities' => ['Проектор', 'Белая доска', 'Wi-Fi', 'Конференц-связь'],
            ],
            [
                'name' => 'Зона отдыха',
                'slug' => 'break-room',
                'icon' => '☕',
                'color' => '#D1FAE5',
                'x_min' => 300,
                'x_max' => 500,
                'y_min' => 400,
                'y_max' => 550,
                'capacity' => 30,
                'amenities' => ['Кофе', 'Чай', 'Снеки', 'Диваны', 'Игры'],
            ],
            [
                'name' => 'Кухня',
                'slug' => 'kitchen',
                'icon' => '🍕',
                'color' => '#FEE2E2',
                'x_min' => 550,
                'x_max' => 750,
                'y_min' => 400,
                'y_max' => 550,
                'capacity' => 15,
                'amenities' => ['Микроволновка', 'Холодильник', 'Чайник', 'Посуда'],
            ],
        ];

        $zones = [];
        foreach ($zonesData as $data) {
            $zones[$data['slug']] = OfficeZone::create($data);
        }

        return $zones;
    }

    /**
     * Создать агентов
     */
    private function createAgents(array $categories, array $zones): void
    {
        $agentsData = [
            // Engineering
            [
                'name' => 'Frontend Developer',
                'slug' => 'frontend-developer',
                'description' => 'Эксперт по React, Vue, Angular',
                'category_slug' => 'engineering',
                'zone_slug' => 'workspace',
                'emoji' => '⚛️',
                'color' => '#3B82F6',
            ],
            [
                'name' => 'Backend Developer',
                'slug' => 'backend-developer',
                'description' => 'Эксперт по PHP, Python, Node.js',
                'category_slug' => 'engineering',
                'zone_slug' => 'workspace',
                'emoji' => '🔧',
                'color' => '#3B82F6',
            ],
            [
                'name' => 'DevOps Engineer',
                'slug' => 'devops-engineer',
                'description' => 'Эксперт по Docker, Kubernetes, CI/CD',
                'category_slug' => 'engineering',
                'zone_slug' => 'workspace',
                'emoji' => '🚀',
                'color' => '#3B82F6',
            ],
            [
                'name' => 'Mobile Developer',
                'slug' => 'mobile-developer',
                'description' => 'Эксперт по iOS, Android, React Native',
                'category_slug' => 'engineering',
                'zone_slug' => 'workspace',
                'emoji' => '📱',
                'color' => '#3B82F6',
            ],

            // Design
            [
                'name' => 'UI Designer',
                'slug' => 'ui-designer',
                'description' => 'Эксперт по пользовательским интерфейсам',
                'category_slug' => 'design',
                'zone_slug' => 'workspace',
                'emoji' => '🎨',
                'color' => '#EC4899',
            ],
            [
                'name' => 'UX Researcher',
                'slug' => 'ux-researcher',
                'description' => 'Эксперт по пользовательскому опыту',
                'category_slug' => 'design',
                'zone_slug' => 'workspace',
                'emoji' => '🔍',
                'color' => '#EC4899',
            ],
            [
                'name' => 'Graphic Designer',
                'slug' => 'graphic-designer',
                'description' => 'Эксперт по графике и иллюстрациям',
                'category_slug' => 'design',
                'zone_slug' => 'workspace',
                'emoji' => '🖼️',
                'color' => '#EC4899',
            ],

            // Marketing
            [
                'name' => 'Content Marketer',
                'slug' => 'content-marketer',
                'description' => 'Эксперт по контент-маркетингу',
                'category_slug' => 'marketing',
                'zone_slug' => 'workspace',
                'emoji' => '✍️',
                'color' => '#10B981',
            ],
            [
                'name' => 'SEO Specialist',
                'slug' => 'seo-specialist',
                'description' => 'Эксперт по поисковой оптимизации',
                'category_slug' => 'marketing',
                'zone_slug' => 'workspace',
                'emoji' => '🔎',
                'color' => '#10B981',
            ],
            [
                'name' => 'Social Media Manager',
                'slug' => 'social-media-manager',
                'description' => 'Эксперт по социальным сетям',
                'category_slug' => 'marketing',
                'zone_slug' => 'workspace',
                'emoji' => '📣',
                'color' => '#10B981',
            ],

            // Sales
            [
                'name' => 'Sales Manager',
                'slug' => 'sales-manager',
                'description' => 'Эксперт по продажам',
                'category_slug' => 'sales',
                'zone_slug' => 'workspace',
                'emoji' => '💰',
                'color' => '#F59E0B',
            ],
            [
                'name' => 'Account Manager',
                'slug' => 'account-manager',
                'description' => 'Эксперт по работе с клиентами',
                'category_slug' => 'sales',
                'zone_slug' => 'workspace',
                'emoji' => '🤝',
                'color' => '#F59E0B',
            ],

            // Support
            [
                'name' => 'Customer Support',
                'slug' => 'customer-support',
                'description' => 'Эксперт по поддержке клиентов',
                'category_slug' => 'support',
                'zone_slug' => 'workspace',
                'emoji' => '💬',
                'color' => '#8B5CF6',
            ],
            [
                'name' => 'Technical Support',
                'slug' => 'technical-support',
                'description' => 'Эксперт по технической поддержке',
                'category_slug' => 'support',
                'zone_slug' => 'workspace',
                'emoji' => '🛠️',
                'color' => '#8B5CF6',
            ],

            // Analytics
            [
                'name' => 'Data Analyst',
                'slug' => 'data-analyst',
                'description' => 'Эксперт по анализу данных',
                'category_slug' => 'analytics',
                'zone_slug' => 'workspace',
                'emoji' => '📈',
                'color' => '#06B6D4',
            ],
            [
                'name' => 'Business Analyst',
                'slug' => 'business-analyst',
                'description' => 'Эксперт по бизнес-анализу',
                'category_slug' => 'analytics',
                'zone_slug' => 'workspace',
                'emoji' => '📋',
                'color' => '#06B6D4',
            ],

            // Management
            [
                'name' => 'Project Manager',
                'slug' => 'project-manager',
                'description' => 'Эксперт по управлению проектами',
                'category_slug' => 'management',
                'zone_slug' => 'meeting-room',
                'emoji' => '📅',
                'color' => '#EF4444',
            ],
            [
                'name' => 'Product Manager',
                'slug' => 'product-manager',
                'description' => 'Эксперт по управлению продуктом',
                'category_slug' => 'management',
                'zone_slug' => 'meeting-room',
                'emoji' => '🎯',
                'color' => '#EF4444',
            ],

            // Creative
            [
                'name' => 'Copywriter',
                'slug' => 'copywriter',
                'description' => 'Эксперт по копирайтингу',
                'category_slug' => 'creative',
                'zone_slug' => 'workspace',
                'emoji' => '📝',
                'color' => '#F97316',
            ],
            [
                'name' => 'Video Editor',
                'slug' => 'video-editor',
                'description' => 'Эксперт по видеомонтажу',
                'category_slug' => 'creative',
                'zone_slug' => 'workspace',
                'emoji' => '🎬',
                'color' => '#F97316',
            ],
        ];

        foreach ($agentsData as $agentData) {
            $category = $categories[$agentData['category_slug']];
            $zone = $zones[$agentData['zone_slug']];

            // Случайная позиция в секторе категории
            $position = $category->getRandomPosition();

            Agent::create([
                'name' => $agentData['name'],
                'slug' => $agentData['slug'],
                'description' => $agentData['description'],
                'category_id' => $category->id,
                'zone_id' => $zone->id,
                'x_position' => $position['x'],
                'y_position' => $position['y'],
                'emoji' => $agentData['emoji'],
                'color' => $agentData['color'],
                'is_active' => true,
                'config' => [
                    'personality' => 'friendly',
                    'response_time' => 'fast',
                    'expertise_level' => 'expert',
                ],
            ]);
        }
    }
}
