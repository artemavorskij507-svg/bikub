<?php

namespace App\Filament\Resources\EmployeeResource\Widgets;

use App\Models\Employee;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class EmployeesOverviewWidget extends BaseWidget
{
    protected function getCards(): array
    {
        $totalEmployees = Employee::count();
        $activeEmployees = Employee::where('status', 'active')->count();
        $verifiedEmployees = Employee::where('is_verified', true)->count();
        $withBackgroundCheck = Employee::where('background_check', true)->count();

        return [
            Card::make('Всего сотрудников', $totalEmployees)
                ->description('В системе')
                ->descriptionIcon('heroicon-s-users')
                ->color('primary'),
            Card::make('Активных', $activeEmployees)
                ->description('Работают сейчас')
                ->descriptionIcon('heroicon-s-check-circle')
                ->color('success'),
            Card::make('Верифицированных', $verifiedEmployees)
                ->description('Прошли верификацию')
                ->descriptionIcon('heroicon-s-shield-check')
                ->color('info'),
            Card::make('С проверкой', $withBackgroundCheck)
                ->description('Проверка биографии пройдена')
                ->descriptionIcon('heroicon-s-clipboard-check')
                ->color('warning'),
        ];
    }
}
