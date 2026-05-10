<?php

namespace App\Providers;

use App\Domain\Routing\Contracts\RouteMatrixProvider;
use App\Domain\Routing\Providers\NullRouteMatrixProvider;
use App\Domain\Routing\Providers\OsrmRouteMatrixProvider;
use App\Models\Claim;
use App\Models\Moving\MovingOrder;
use App\Models\Order;
use App\Models\Partner;
use App\Models\Payout;
use App\Models\PricingRule;
use App\Models\RoadsideEmergency;
use App\Models\ServiceType;
use App\Models\User;
use App\Modules\Classifieds\Models\ClassifiedAd;
use App\Modules\Classifieds\Observers\ClassifiedAdObserver;
use App\Observers\ClaimObserver;
use App\Observers\ModelObserver;
use App\Observers\MovingOrderObserver;
use App\Observers\OrderObserver;
use App\Observers\PayoutObserver;
use App\Observers\RoadsideEmergencyObserver;
use App\Services\EcoDisposal\Contracts\EcoRecommendationEngineInterface;
use App\Services\EcoDisposal\EcoRecommendationEngine;
use App\Services\Errand\ErrandPricingService;
use App\Services\FeatureFlags\Context;
use App\Services\FeatureFlags\FeatureFlagger;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Filament\Forms\Components\KeyValue;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Load Debugbar only when explicitly enabled.
        if ($this->app->environment('local', 'testing', 'dev') && filter_var(env('DEBUGBAR_ENABLED', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->app->register(\Barryvdh\Debugbar\ServiceProvider::class);
        }

        // Load Telescope only when explicitly enabled.
        if ($this->app->environment('local') && filter_var(env('TELESCOPE_ENABLED', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(\Laravel\Telescope\TelescopeApplicationServiceProvider::class);
        }

        // Bind ECO recommendation engine interface to rule-based implementation (v1)
        $this->app->bind(EcoRecommendationEngineInterface::class, EcoRecommendationEngine::class);

        // Register Errand pricing service
        $this->app->singleton(ErrandPricingService::class, function ($app) {
            return new ErrandPricingService;
        });

        $this->app->singleton(RouteMatrixProvider::class, function () {
            $provider = (string) config('routing.default_provider', 'null');
            $osrmEnabled = (bool) config('routing.osrm.enabled', false);

            if ($provider === 'osrm' && $osrmEnabled) {
                return new OsrmRouteMatrixProvider;
            }

            return new NullRouteMatrixProvider;
        });

        // Переопределяем FilamentManager для безопасной обработки null пользователя
        // Делаем это здесь, чтобы переопределить до того, как Filament создаст свой экземпляр
        $this->app->singleton('filament', function ($app) {
            $original = new \Filament\FilamentManager($app);

            // Создаем декоратор
            return new class($original)
            {
                protected $original;

                // Методы FilamentManager, которые принимают пользователя первым аргументом
                protected $userMethods = ['getUserAvatarUrl', 'getUserName'];

                public function __construct($original)
                {
                    $this->original = $original;
                }

                public function getUserAvatarUrl($user): ?string
                {
                    if (! $user) {
                        return null;
                    }
                    try {
                        return $this->original->getUserAvatarUrl($user);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('Failed to get user avatar URL', ['error' => $e->getMessage()]);

                        return null;
                    }
                }

                public function getUserName($user): ?string
                {
                    if (! $user) {
                        return null;
                    }
                    try {
                        return $this->original->getUserName($user);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('Failed to get user name', ['error' => $e->getMessage()]);

                        return null;
                    }
                }

                public function __call($method, $args)
                {
                    // Проверяем, является ли метод одним из тех, что принимает пользователя
                    if (in_array($method, $this->userMethods) && isset($args[0]) && $args[0] === null) {
                        return null;
                    }

                    // Для методов, принимающих пользователя, проверяем первый аргумент
                    if (in_array($method, $this->userMethods) && (! isset($args[0]) || $args[0] === null)) {
                        return null;
                    }

                    try {
                        return $this->original->$method(...$args);
                    } catch (\TypeError $e) {
                        // Если ошибка типа из-за null пользователя, возвращаем null
                        if (str_contains($e->getMessage(), 'must be of type') && str_contains($e->getMessage(), 'null given')) {
                            \Illuminate\Support\Facades\Log::warning("Filament method {$method} called with null user", ['error' => $e->getMessage()]);

                            return null;
                        }
                        throw $e;
                    } catch (\Throwable $e) {
                        throw $e;
                    }
                }

                public function __get($property)
                {
                    return $this->original->$property;
                }
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureFilamentKeyValueSafety();

        if ($this->app->runningInConsole()) {
            $this->commands([
                // \App\Console\Commands\VegvesenSyncCommand::class,
                // \App\Console\Commands\VegvesenIngestIncidentsCommand::class,
                // \App\Console\Commands\VegvesenIngestTravelTimesCommand::class,
                // \App\Console\Commands\AssistantBroadcastCommand::class,
            ]);
        } else {
            $locale = session('locale', config('app.locale'));
            App::setLocale($locale);
        }

        // Blade directive for feature flags
        Blade::if('feature', function (string $key) {
            $user = auth()->user();
            $role = null;
            $orgId = null;

            if ($user) {
                // Get first role name or null
                $firstRole = $user->roles()->first();
                $role = $firstRole?->name;
                // Get org_id if exists (check if property exists)
                $orgId = property_exists($user, 'org_id') ? $user->org_id : null;
            }

            $context = new Context(
                orgId: $orgId,
                zoneId: request()->header('X-Zone-Id') ?? null,
                serviceTypeId: request()->header('X-Service-Type-Id') ?? null,
                userId: auth()->id(),
                role: $role,
            );

            return app(FeatureFlagger::class)->enabled($key, $context);
        });

        // Register observers
        if (class_exists(MovingOrder::class) && class_exists(MovingOrderObserver::class)) {
            MovingOrder::observe(MovingOrderObserver::class);
        }
        if (class_exists(Payout::class) && class_exists(PayoutObserver::class)) {
            Payout::observe(PayoutObserver::class);
        }
        if (class_exists(RoadsideEmergency::class) && class_exists(RoadsideEmergencyObserver::class)) {
            RoadsideEmergency::observe(RoadsideEmergencyObserver::class);
        }
        if (class_exists(Claim::class) && class_exists(ClaimObserver::class)) {
            Claim::observe(ClaimObserver::class);
        }
        if (class_exists(Order::class) && class_exists(OrderObserver::class)) {
            Order::observe(OrderObserver::class);
        }

        // Generic audit observer for selected models (safe-guarded with class_exists)
        if (class_exists(\App\Observers\ModelObserver::class)) {
            if (class_exists(PricingRule::class)) {
                PricingRule::observe(ModelObserver::class);
            }
            if (class_exists(ServiceType::class)) {
                ServiceType::observe(ModelObserver::class);
            }
            if (class_exists(Partner::class)) {
                Partner::observe(ModelObserver::class);
            }
            if (class_exists(User::class)) {
                User::observe(ModelObserver::class);
            }
        }

        // Subscribe to auth events to log login/logout/failed
        if ($this->app->bound('events')) {
            $this->app['events']->subscribe(\App\Listeners\AuthEventSubscriber::class);
        }

        if (class_exists(ClassifiedAd::class) && class_exists(ClassifiedAdObserver::class)) {
            ClassifiedAd::observe(ClassifiedAdObserver::class);
        }

        // Register AdminIpRule observer to enforce critical protections and audit logging
        if (class_exists(\App\Models\AdminIpRule::class) && class_exists(\App\Observers\AdminIpRuleObserver::class)) {
            \App\Models\AdminIpRule::observe(\App\Observers\AdminIpRuleObserver::class);
        }

        // Register ApiKey observer for audit logging (created, rotated, revoked)
        if (class_exists(\App\Models\ApiKey::class) && class_exists(\App\Observers\ApiKeyObserver::class)) {
            \App\Models\ApiKey::observe(\App\Observers\ApiKeyObserver::class);
        }

        // Manually register Livewire component for Classifieds module
        if (class_exists(\Livewire\Livewire::class) && class_exists(\App\Modules\Classifieds\Http\Livewire\UserAdsTable::class)) {
            \Livewire\Livewire::component('user-ads-table', \App\Modules\Classifieds\Http\Livewire\UserAdsTable::class);
        }

        // Register Health checks for system monitoring
        if (class_exists(\Spatie\Health\Facades\Health::class)) {
            \Spatie\Health\Facades\Health::checks([
                \Spatie\Health\Checks\Checks\DatabaseCheck::new(),
                \Spatie\Health\Checks\Checks\CacheCheck::new(),
                \Spatie\Health\Checks\Checks\UsedDiskSpaceCheck::new(),
                \Spatie\Health\Checks\Checks\EnvironmentCheck::new(),
                \Spatie\Health\Checks\Checks\QueueCheck::new(),
                \Spatie\Health\Checks\Checks\RedisCheck::new(),
                \Spatie\Health\Checks\Checks\OptimizedAppCheck::new(),
            ]);
        }

        // Normalize generated URLs to the current request host:port.
        if (! app()->runningInConsole()) {
            $requestHost = request()->getHttpHost();
            if (is_string($requestHost) && $requestHost !== '') {
                URL::forceRootUrl((request()->isSecure() ? 'https://' : 'http://').$requestHost);
            }
        }
    }


    private function configureFilamentKeyValueSafety(): void
    {
        if (! class_exists(KeyValue::class)) {
            return;
        }

        KeyValue::configureUsing(function (KeyValue $component): void {
            $component->afterStateHydrated(function (KeyValue $component, $state): void {
                if (! is_array($state)) {
                    return;
                }

                $normalized = [];
                foreach ($state as $key => $value) {
                    $normalized[(string) $key] = self::normalizeKeyValueValue($value, false);
                }

                $component->state($normalized);
            });

            $component->dehydrateStateUsing(function (?array $state): array {
                $normalized = [];

                foreach (($state ?? []) as $key => $value) {
                    $key = (string) $key;
                    if ($key === '') {
                        continue;
                    }

                    $normalized[$key] = self::normalizeKeyValueValue($value, true);
                }

                return $normalized;
            });
        });
    }

    private static function normalizeKeyValueValue(mixed $value, bool $nullable): ?string
    {
        if ($value === null || $value === '') {
            return $nullable ? null : '';
        }

        if (is_array($value) || is_object($value)) {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return $encoded === false ? '{}' : $encoded;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }

}
