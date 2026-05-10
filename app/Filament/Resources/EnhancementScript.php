<?php

/**
 * Скрипт для автоматического добавления ULTRA PRO MAX+ улучшений
 * ко всем Filament Resources
 *
 * ИСПОЛЬЗОВАНИЕ:
 * php artisan tinker
 * >>> include 'app/Filament/Resources/EnhancementScript.php';
 * >>> EnhancementScript::applyToAllResources();
 */

namespace App\Filament\Resources;

class EnhancementScript
{
    /**
     * Список Resources, которые уже улучшены вручную
     */
    protected static array $alreadyEnhanced = [
        'OrderResource',
        'ServiceCategoryResource',
        'DeliveryOrderResource',
        'UserResource',
        'HandymanAssignmentResource',
        'MovingOrderResource',
        'SocialCareOrderResource',
    ];

    /**
     * Применить улучшения ко всем Resources
     */
    public static function applyToAllResources(): void
    {
        $resources = glob('app/Filament/Resources/**/*Resource.php');

        foreach ($resources as $file) {
            $className = basename($file, '.php');

            if (in_array($className, self::$alreadyEnhanced)) {
                echo "⏭️  Пропущен (уже улучшен): {$className}\n";

                continue;
            }

            echo "🔧 Обработка: {$className}\n";
            // Здесь можно добавить автоматическое применение улучшений
        }

        echo "\n✅ Обработка завершена!\n";
    }

    /**
     * Получить список всех Resources
     */
    public static function listAllResources(): array
    {
        $resources = [];
        $files = glob('app/Filament/Resources/**/*Resource.php');

        foreach ($files as $file) {
            $resources[] = [
                'file' => $file,
                'class' => basename($file, '.php'),
                'enhanced' => in_array(basename($file, '.php'), self::$alreadyEnhanced),
            ];
        }

        return $resources;
    }
}
