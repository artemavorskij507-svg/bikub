<?php

use App\Livewire\ShopProfile;
use App\Modules\Classifieds\Controllers\AdIntegrationController;
use App\Modules\Classifieds\Http\Controllers\AccountClassifiedsController;
use Illuminate\Support\Facades\Route;

// Публичная страница магазина
Route::get('/shops/{slug}', ShopProfile::class)->name('shops.show');

// Маршруты аккаунта для управления объявлениями
Route::middleware('auth')
    ->prefix('account/classifieds')
    ->name('account.classifieds.')
    ->group(function () {
        // Список объявлений пользователя
        Route::get('/', \App\Livewire\MyAds::class)->name('my-ads');

        // Messages (Inbox)
        Route::get('/messages', \App\Livewire\MyMessages::class)->name('messages');

        // Favorites
        Route::get('/favorites', \App\Livewire\Favorites::class)->name('favorites');

        // Seller Hub - управление магазином
        Route::get('/shop', \App\Livewire\Seller\ManageShops::class)->name('shop');

        // Создание нового объявления
        Route::get('/create', function () {
            $categories = \App\Modules\Classifieds\Models\AdCategory::where('is_active', true)->get();

            return view('classifieds.create', compact('categories'));
        })->name('create');

        // Favorite actions
        Route::post('/{ad}/favorite', [AccountClassifiedsController::class, 'favorite'])->name('favorite');
        Route::delete('/{ad}/favorite', [AccountClassifiedsController::class, 'unfavorite'])->name('unfavorite');
        // Bump action
        Route::post('/{ad}/bump', [AccountClassifiedsController::class, 'bump'])->name('bump');

        // Сохранение нового объявления
        Route::post('/', [\App\Modules\Classifieds\Controllers\ClassifiedAdController::class, 'store'])
            ->name('store');

        // Интеграция с модулем доставки
        Route::post('/{ad}/delivery', [AdIntegrationController::class, 'createDeliveryRequest'])
            ->name('delivery');

        // Пометить объявление как проданное
        Route::post('/{ad}/sold', [AdIntegrationController::class, 'markAsSold'])
            ->name('sold');

        // Редактирование объявления
        Route::get('/{ad}/edit', function (\App\Modules\Classifieds\Models\ClassifiedAd $ad) {
            if ($ad->user_id !== auth()->id()) {
                abort(403);
            }
            $categories = \App\Modules\Classifieds\Models\AdCategory::where('is_active', true)->get();

            return view('classifieds.edit', compact('ad', 'categories'));
        })->name('edit');

        // Обновление объявления
        Route::put('/{ad}', [\App\Modules\Classifieds\Controllers\ClassifiedAdController::class, 'update'])
            ->name('update');

        // Удаление объявления
        Route::delete('/{ad}', function (\App\Modules\Classifieds\Models\ClassifiedAd $ad) {
            if ($ad->user_id !== auth()->id()) {
                abort(403);
            }
            $ad->delete();

            return redirect()->route('account.classifieds.my-ads')
                ->with('success', 'Объявление успешно удалено');
        })->name('destroy');

        // Просмотр объявления в аккаунте (должен быть ПОСЛЕДНИМ, так как использует slug)
        Route::get('/{ad:slug}', [AccountClassifiedsController::class, 'show'])->name('show');
    });
