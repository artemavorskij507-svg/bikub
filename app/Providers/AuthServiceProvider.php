<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\Task::class => \App\Policies\TaskPolicy::class,
        \App\Models\Order::class => \App\Policies\OrderPolicy::class,
        \App\Models\Claim::class => \App\Policies\ClaimPolicy::class,
        \App\Models\Delivery\DeliveryOrder::class => \App\Policies\DeliveryOrderPolicy::class,

        // Roadside & Tow
        \App\Models\RoadsideEmergency::class => \App\Policies\RoadsideEmergencyPolicy::class,
        \App\Models\RoadsidePreset::class => \App\Policies\RoadsidePresetPolicy::class,
        \App\Models\RoadHelperProfile::class => \App\Policies\RoadHelperProfilePolicy::class,
        \App\Models\Partner::class => \App\Policies\RoadsidePartnerPolicy::class,
        \App\Models\VehicleInspectionRequest::class => \App\Policies\VehicleInspectionRequestPolicy::class,
        \App\Models\VehicleInspectionPreset::class => \App\Policies\VehicleInspectionPresetPolicy::class,
        \App\Domain\Operations\Models\ServiceJob::class => \App\Policies\ServiceJobPolicy::class,
        \App\Models\Operations\ServiceJob::class => \App\Policies\ServiceJobPolicy::class,
        \App\Models\Operations\Assignment::class => \App\Policies\AssignmentPolicy::class,
        \App\Models\Operations\Executor::class => \App\Policies\ExecutorPolicy::class,
        \App\Models\Operations\OperationException::class => \App\Policies\OperationExceptionPolicy::class,
        \App\Domain\Dispatch\Models\Assignment::class => \App\Policies\AssignmentPolicy::class,
        \App\Domain\Dispatch\Models\ExecutorShift::class => \App\Policies\ExecutorShiftPolicy::class,
        \App\Domain\Dispatch\Models\ExecutorBreak::class => \App\Policies\ExecutorBreakPolicy::class,
        \App\Domain\Dispatch\Models\DispatchRuleSet::class => \App\Policies\DispatchRuleSetPolicy::class,
        \App\Domain\Operations\Models\Executor::class => \App\Policies\ExecutorPolicy::class,
        \App\Domain\Exceptions\Models\OperationException::class => \App\Policies\OperationExceptionPolicy::class,
        \App\Domain\Ops\Models\SavedOpsFilter::class => \App\Policies\SavedOpsFilterPolicy::class,
        \App\Domain\AgentOS\Models\AgentRun::class => \App\Policies\AgentRunPolicy::class,
        \App\Domain\AgentOS\Models\AgentStep::class => \App\Policies\AgentStepPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Define gates for common permissions
        \Illuminate\Support\Facades\Gate::define('manage-tasks', function (User $user) {
            return $user->hasRole('admin') || $user->hasRole('operator');
        });

        \Illuminate\Support\Facades\Gate::define('manage-orders', function (User $user) {
            return $user->hasRole('admin') || $user->hasRole('operator');
        });

        \Illuminate\Support\Facades\Gate::define('view-reports', function (User $user) {
            return $user->hasRole('admin') || $user->hasRole('operator');
        });

        \Illuminate\Support\Facades\Gate::define('manage-users', function (User $user) {
            return $user->hasRole('admin');
        });

        \Illuminate\Support\Facades\Gate::define('manage-settings', function (User $user) {
            return $user->hasRole('admin');
        });
    }
}
