<?php

namespace App\Providers;

use App\Filament\Pages\Dispatch;
use App\Filament\Pages\ExceptionSlaCenter;
use App\Filament\Pages\LiveOperationsMap;
use App\Filament\Pages\OperationExceptionsList;
use App\Filament\Pages\OperationsCoreBoard;
use App\Filament\Pages\RoadsideDashboard;
use App\Filament\Pages\RoadsideDispatchBoard;
use App\Filament\Pages\ServiceJobsBoard;
use App\Filament\Pages\UnifiedOperationsCore;
use App\Filament\Resources\RoadHelperProfileResource;
use App\Filament\Resources\OperationExceptionResource;
use App\Filament\Resources\RoadsideEmergencyResource;
use App\Filament\Resources\RoadsidePartnerResource;
use App\Filament\Resources\RoadsidePresetResource;
use App\Filament\Resources\ServiceJobResource;
use App\Filament\Resources\VehicleInspectionPresetResource;
use App\Filament\Resources\VehicleInspectionRequestResource;
use Filament\Facades\Filament;
use Illuminate\Support\ServiceProvider;

class FilamentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // В Filament v2 ресурсы автоматически подтягиваются через config/filament.php
        // resources_path указывает на app/Filament/Resources, Filament сканирует эту директорию
        // Не нужно явно регистрировать ресурсы здесь
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // fix: register Roadside & Tow resources EARLY, before serving() callback
        Filament::registerResources([
            RoadsideEmergencyResource::class,
            RoadsidePresetResource::class,
            RoadHelperProfileResource::class,
            RoadsidePartnerResource::class,
            VehicleInspectionRequestResource::class,
            VehicleInspectionPresetResource::class,
            ServiceJobResource::class,
            OperationExceptionResource::class,
        ]);

        // fix: register Roadside & Tow pages EARLY
        Filament::registerPages([
            RoadsideDashboard::class,
            RoadsideDispatchBoard::class,
            Dispatch::class,
            UnifiedOperationsCore::class,
            LiveOperationsMap::class,
            OperationsCoreBoard::class,
            ServiceJobsBoard::class,
            ExceptionSlaCenter::class,
            OperationExceptionsList::class,
            \App\Filament\Pages\Security\TwoFactorSetup::class,
        ]);

        // NOTE: Removed custom Filament::navigation() to restore automatic menu building by resources/pages

        Filament::serving(function () {
            // Filament v2 uses registerStyles instead of registerViteTheme
            // Filament::registerStyles([
            //     route('filament.asset', ['id' => 'filament-custom-styles', 'path' => 'css/filament.css']),
            // ]);
        });

        // Аутентификация по умолчанию Filament (через конфиг) — без кастомной привязки здесь

        // Регистрация страницы аналитики временно отключена, чтобы
        // исключить обращение к несуществующему маршруту filament.pages.analytics.
        // if (class_exists(\App\Filament\Pages\Analytics::class)) {
        //     \Filament\Facades\Filament::registerPages([
        //         \App\Filament\Pages\Analytics::class,
        //     ]);
        // }

        // Отключено: кастомный JS может вмешиваться в форму логина
        // Filament::registerScripts([
        //     asset('js/filament.js'),
        // ]);
    }
}
