<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VirtualOfficeController;

/*
|--------------------------------------------------------------------------
| Virtual Office Web Routes
|--------------------------------------------------------------------------
|
| Маршруты для веб-интерфейса виртуального офиса.
|
*/

Route::prefix('virtual-office')->group(function () {
    // Главная страница виртуального офиса
    Route::get('/', [VirtualOfficeController::class, 'index'])->name('virtual-office.index');

    // API маршруты (для AJAX запросов)
    Route::get('/stats', [VirtualOfficeController::class, 'stats'])->name('virtual-office.stats');
    Route::get('/agents', [VirtualOfficeController::class, 'agents'])->name('virtual-office.agents');
    Route::get('/agents/{id}', [VirtualOfficeController::class, 'agent'])->name('virtual-office.agent');
    Route::post('/agents/{id}/move', [VirtualOfficeController::class, 'moveAgent'])->name('virtual-office.move-agent');
    Route::get('/zones', [VirtualOfficeController::class, 'zones'])->name('virtual-office.zones');
    Route::get('/categories', [VirtualOfficeController::class, 'categories'])->name('virtual-office.categories');
    Route::get('/tasks', [VirtualOfficeController::class, 'tasks'])->name('virtual-office.tasks');
    Route::post('/tasks', [VirtualOfficeController::class, 'createTask'])->name('virtual-office.create-task');
    Route::get('/agents/{agentId}/messages', [VirtualOfficeController::class, 'messages'])->name('virtual-office.messages');
    Route::post('/agents/{agentId}/messages', [VirtualOfficeController::class, 'sendMessage'])->name('virtual-office.send-message');
});
