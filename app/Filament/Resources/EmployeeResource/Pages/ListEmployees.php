<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use App\Models\Employee;
use App\Models\User;
use Filament\Pages\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Schema;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->seedLocalDemoEmployeesIfEmpty();
    }

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            EmployeeResource\Widgets\EmployeesOverviewWidget::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Все')
                ->badge(Employee::count()),
            'active' => Tab::make('Активные')
                ->badge(Employee::where('status', 'active')->count())
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'active')),
            'inactive' => Tab::make('Неактивные')
                ->badge(Employee::where('status', 'inactive')->count())
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'inactive')),
            'on_leave' => Tab::make('В отпуске')
                ->badge(Employee::where('status', 'on_leave')->count())
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'on_leave')),
            'verified' => Tab::make('Верифицированные')
                ->badge(Employee::where('is_verified', true)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('is_verified', true)),
        ];
    }

    protected function seedLocalDemoEmployeesIfEmpty(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        if (! Schema::hasTable('employees') || ! Schema::hasTable('users')) {
            return;
        }

        if (Employee::query()->exists()) {
            return;
        }

        $profiles = [
            ['name' => 'Demo Courier One', 'email' => 'employee1@glf.no', 'position' => 'Courier', 'status' => 'active'],
            ['name' => 'Demo Dispatcher Two', 'email' => 'employee2@glf.no', 'position' => 'Dispatcher', 'status' => 'active'],
            ['name' => 'Demo Worker Three', 'email' => 'employee3@glf.no', 'position' => 'Field Worker', 'status' => 'inactive'],
        ];

        foreach ($profiles as $index => $profile) {
            $user = User::query()->firstOrCreate(
                ['email' => $profile['email']],
                [
                    'name' => $profile['name'],
                    'password' => 'password',
                    'is_active' => true,
                    'timezone' => 'Europe/Oslo',
                    'locale' => 'ru',
                ]
            );

            Employee::query()->create([
                'user_id' => $user->id,
                'employee_number' => 'EMP-'.now()->format('ymd').'-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'first_name' => explode(' ', $profile['name'])[0] ?? 'Demo',
                'last_name' => explode(' ', $profile['name'])[1] ?? 'Employee',
                'phone' => '+479000000'.($index + 1),
                'email' => $profile['email'],
                'position' => $profile['position'],
                'status' => $profile['status'],
                'is_verified' => $index < 2,
                'background_check' => $index === 0,
                'hire_date' => now()->subDays(20 + ($index * 5))->toDateString(),
                'skills' => ['delivery', 'support'],
                'metadata' => ['source' => 'local_demo_seed'],
                'is_online' => $profile['status'] === 'active',
                'workload_score' => $profile['status'] === 'active' ? 0.35 + ($index * 0.1) : 0.0,
                'last_ping_at' => now()->subMinutes(5 + ($index * 3)),
            ]);
        }
    }
}
