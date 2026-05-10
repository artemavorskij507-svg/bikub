<?php

use App\Http\Controllers\Account\BillingController as AccountBillingController;
use App\Http\Controllers\Account\ClientContextController;
use App\Http\Controllers\Account\DashboardController as AccountDashboardController;
use App\Http\Controllers\Account\DeliveryController as AccountDeliveryController;
use App\Http\Controllers\Account\NewOrderController;
use App\Http\Controllers\Account\NotificationController;
use App\Http\Controllers\Account\NotificationInboxController as AccountNotificationInboxController;
use App\Http\Controllers\Account\NotificationsController as AccountNotificationsController;
use App\Http\Controllers\Account\OrderReviewController;
use App\Http\Controllers\Account\OrdersController as AccountOrdersController;
use App\Http\Controllers\Account\ProfileController as AccountProfileController;
use App\Http\Controllers\Account\RepairProjectController;
use App\Http\Controllers\Account\SecurityController as AccountSecurityController;
use App\Http\Controllers\Account\SocialCareController as AccountSocialCareController;
use App\Http\Controllers\Auth\EidLoginController;
use App\Http\Controllers\Frontend\CartController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\FastOrderController;
use App\Http\Controllers\Public\CheckoutController;
use App\Http\Controllers\Public\Handyman\HandymanBookingController;
use App\Http\Controllers\Public\Handyman\HandymanCatalogController;
use App\Http\Controllers\Public\RoadsideController;
use App\Http\Controllers\Public\RoadsideSOSController;
use App\Http\Controllers\Public\RoadsideTrackingController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\OrderTrackerController;
use App\Http\Controllers\PartnerPortalWebController;
use App\Http\Controllers\WorkerOnboardingController;
use App\Modules\Classifieds\Models\ClassifiedAd;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Filament Ops/Agent legacy slugs compatibility redirects.
Route::redirect('/admin/obshhenieskomandoiii-agentov', '/admin/a-i-agent-team-chat', 302);

// Block account-based order creation: redirect any account/new* to account dashboard
Route::match(['GET', 'POST'], 'account/new/{any?}', function () {
    return redirect()->route('account.dashboard');
})->where('any', '.*');

// Личный кабинет сотрудников (Courier, Executor, Handyman)
Route::middleware(['auth'])
    ->prefix('lk')
    ->name('lk.')
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\Lk\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/refresh', [\App\Http\Controllers\Lk\DashboardController::class, 'refresh'])->name('dashboard.refresh');

        // Orders
        Route::get('/orders', [\App\Http\Controllers\Lk\OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [\App\Http\Controllers\Lk\OrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/action', [\App\Http\Controllers\Lk\OrderActionController::class, 'handle'])->name('orders.action');

        // Wallet
        Route::get('/wallet', [\App\Http\Controllers\Lk\WalletController::class, 'index'])->name('wallet');
        Route::post('/wallet/request-payout', [\App\Http\Controllers\Lk\WalletController::class, 'requestPayout'])->name('wallet.request-payout');

        // Settings
        Route::get('/settings', [\App\Http\Controllers\Lk\SettingsController::class, 'index'])->name('settings');
        Route::post('/settings', [\App\Http\Controllers\Lk\SettingsController::class, 'update'])->name('settings.update');

        // Notifications
        Route::get('/notifications', [\App\Http\Controllers\Lk\NotificationController::class, 'index'])->name('notifications');
        Route::post('/notifications/{id}/read', [\App\Http\Controllers\Lk\NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::post('/notifications/read-all', [\App\Http\Controllers\Lk\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

        // Profile
        Route::get('/profile', [\App\Http\Controllers\Lk\ProfileController::class, 'index'])->name('profile');
        Route::post('/profile', [\App\Http\Controllers\Lk\ProfileController::class, 'update'])->name('profile.update');

        // Assistant
        Route::post('/assistant/message', [\App\Http\Controllers\Lk\AssistantController::class, 'message'])->name('assistant.message');

        // Schedule
        Route::get('/schedule', [\App\Http\Controllers\Lk\ScheduleController::class, 'index'])->name('schedule');
        Route::post('/schedule/update-availability', [\App\Http\Controllers\Lk\ScheduleController::class, 'updateAvailability'])->name('schedule.update-availability');

        // Roadside Jobs
        Route::get('/roadside-jobs', [\App\Http\Controllers\Lk\RoadsideJobController::class, 'index'])->name('roadside-jobs.index');
        Route::get('/roadside-jobs/{job}', [\App\Http\Controllers\Lk\RoadsideJobController::class, 'show'])->name('roadside-jobs.show');
        Route::post('/roadside-jobs/{job}/action', [\App\Http\Controllers\Lk\RoadsideJobActionController::class, 'handle'])->name('roadside-jobs.action');

        // Support
        Route::get('/support', [\App\Http\Controllers\Lk\SupportController::class, 'index'])->name('support');
        Route::post('/support/tickets', [\App\Http\Controllers\Lk\SupportController::class, 'store'])->name('support.tickets.store');
        Route::get('/support/tickets/{ticket}', [\App\Http\Controllers\Lk\SupportController::class, 'show'])->name('support.tickets.show');
        Route::post('/support/tickets/{ticket}/messages', [\App\Http\Controllers\Lk\SupportController::class, 'addMessage'])->name('support.tickets.messages.store');

        // Worker Status
        Route::post('/worker/status', [\App\Http\Controllers\Lk\WorkerStatusController::class, 'update'])->name('worker.status');
        Route::post('/worker/status/toggle', [\App\Http\Controllers\Lk\WorkerStatusController::class, 'toggle'])->name('worker.status.toggle');

        // Executor Jobs (интеграция executor в ЛК)
        Route::get('/executor/jobs', [\App\Http\Controllers\Lk\ExecutorJobsController::class, 'index'])->name('executor.jobs.index');
        Route::get('/executor/jobs/{assignment}', [\App\Http\Controllers\Lk\ExecutorJobsController::class, 'show'])->name('executor.jobs.show');
        Route::post('/executor/jobs/{assignment}/accept', [\App\Http\Controllers\Lk\ExecutorJobsController::class, 'accept'])->name('executor.jobs.accept');
        Route::post('/executor/jobs/{assignment}/decline', [\App\Http\Controllers\Lk\ExecutorJobsController::class, 'decline'])->name('executor.jobs.decline');
        Route::post('/executor/jobs/{assignment}/status', [\App\Http\Controllers\Lk\ExecutorJobsController::class, 'updateStatus'])->name('executor.jobs.status');
    });

// Classifieds public listing (board) – explicit route so it doesn't fall into /catalog fallback
Route::get('/classifieds', [\App\Http\Controllers\ClassifiedsIndexController::class, 'index'])->name('classifieds.index');

// Публичная страница одного объявления по slug
Route::get('/classifieds/{ad:slug}', function (ClassifiedAd $ad) {
    $ad->load(['images', 'category', 'shop', 'user']);
    // Убеждаемся, что изображения загружены
    $ad->images;

    return view('classifieds.show', compact('ad'));
})->name('classifieds.show')->middleware(\App\Http\Middleware\TrackAdView::class);

// Публичная страница магазина
Route::get('/shops/{slug}', \App\Livewire\ShopProfile::class)->name('shops.show');

// SEO Sitemap для classifieds
Route::get('/sitemap-classifieds.xml', [\App\Modules\Classifieds\Controllers\SitemapController::class, 'index']);

Route::get('/catalog', [PublicController::class, 'catalog'])->name('public.catalog.index');
// Переадресация старых slug на актуальные категории (чтобы не падать в /catalog)
Route::get('/category/assistant', fn () => redirect()->route('public.category', ['slug' => 'personal-task']));
Route::get('/category/roadside', fn () => redirect()->route('public.category', ['slug' => 'tow']));
Route::get('/category/care', fn () => redirect()->route('public.category', ['slug' => 'social-help']));
Route::get('/category/food', [PublicController::class, 'food'])->name('public.category.food');
Route::get('/category/gfl', fn () => redirect()->route('public.restaurants.index'))->name('public.category.gfl');
// Специальная категория для доски объявлений
Route::get('/category/classifieds', fn () => redirect()->route('classifieds.index'))->name('public.category.classifieds');
Route::get('/category', fn () => view('public.categories'))->name('public.categories');
Route::get('/category/{slug}', [PublicController::class, 'category'])->name('public.category');
Route::get('/catalog/{categoryCode}', [PublicController::class, 'categoryServices'])->name('catalog.category');
Route::get('/order/{serviceCode}', [PublicController::class, 'orderForm'])->name('order.form');
Route::get('/service/{slug}', [PublicController::class, 'service'])->name('public.service');

Route::get('/checkout/{scenario}', [CheckoutController::class, 'show'])
    ->where('scenario', '[A-Za-z0-9_.-]+')
    ->name('checkout.show');
Route::post('/checkout/{scenario}', [CheckoutController::class, 'store'])
    ->where('scenario', '[A-Za-z0-9_.-]+')
    ->middleware(['auth', 'throttle:orders'])
    ->name('checkout.store');
Route::get('/orders/{order}/track', [OrderTrackerController::class, 'show'])
    ->middleware(['auth'])
    ->name('orders.track');

// Короткие URL → модульные лендинги категорий (чтобы не падать в fallback /catalog)
Route::get('/assistant', fn () => redirect()->route('public.category', ['slug' => 'personal-task']))->name('assistant');
Route::get('/roadside', fn () => redirect()->route('public.category', ['slug' => 'tow']))->name('roadside');
Route::get('/delivery', fn () => redirect()->route('public.category', ['slug' => 'delivery']))->name('delivery');
Route::get('/eco', fn () => redirect()->route('public.category', ['slug' => 'eco']))->name('eco');
Route::get('/moving', fn () => redirect()->route('public.category', ['slug' => 'moving']))->name('moving');
Route::get('/handyman', fn () => redirect()->route('public.category', ['slug' => 'handyman']))->name('handyman.landing');
Route::get('/personal-task', fn () => redirect()->route('public.category', ['slug' => 'personal-task']))->name('personal-task');
Route::get('/tow', fn () => redirect()->route('public.category', ['slug' => 'tow']))->name('tow');

// Старый /care → новая социальная помощь
Route::get('/care', fn () => redirect()->route('public.category', ['slug' => 'social-help']))->name('care');
Route::get('/market', [PublicController::class, 'market'])->name('market');
Route::get('/rent', [PublicController::class, 'rent'])->name('rent');
Route::get('/shuttle', [PublicController::class, 'shuttle'])->name('shuttle');
Route::get('/master', [PublicController::class, 'master'])->name('master');
Route::get('/food', fn () => redirect('/category/gfl', 301))->name('food');
Route::get('/gfl', fn () => redirect('/category/gfl', 301))->name('gfl');
// Прямой маршрут для социальной помощи, чтобы /social-help не падал в fallback /catalog
Route::get('/social-help', fn () => redirect()->route('public.category', ['slug' => 'social-help']))->name('social-help');
Route::get('/healthz', fn () => response()->json(['status' => 'ok'], 200))->name('api.health');

// Публичные маршруты для магазинов
Route::prefix('stores')->name('public.stores.')->group(function () {
    Route::get('/', [PublicController::class, 'storesIndex'])->name('index');
    Route::get('/{slug}', [PublicController::class, 'storeShow'])->name('show');
});

// Публичные маршруты для ресторанов
Route::prefix('restaurants')->name('public.restaurants.')->group(function () {
    Route::get('/', [PublicController::class, 'restaurantsIndex'])->name('index');
    Route::get('/{slug}', [PublicController::class, 'restaurantShow'])->name('show');
});

// Публичный маршрут для списка всех услуг
Route::get('/services', [PublicController::class, 'servicesIndex'])->name('public.services.index');

Route::get('/cart', [CartController::class, 'index'])->name('public.cart.index');
Route::post('/cart/optimize', [CartController::class, 'optimize'])->name('public.cart.optimize');

Route::prefix('roadside')
    ->name('public.roadside.')
    ->group(function () {
        Route::get('/', [RoadsideController::class, 'index'])->name('index');
        Route::get('/order', [RoadsideController::class, 'orderForm'])->name('order');
        Route::post('/order', [RoadsideController::class, 'submitOrder'])->name('order.submit');
        Route::get('/thanks', [RoadsideController::class, 'thanks'])->name('thanks');
        Route::get('/form', [RoadsideController::class, 'showForm'])->name('form');
        Route::post('/submit', [RoadsideController::class, 'submit'])->name('submit');

        Route::get('/tracking/{order}', [RoadsideTrackingController::class, 'show'])->name('tracking.show');

        Route::get('/sos', [RoadsideSOSController::class, 'index'])->name('sos');
        Route::post('/sos', [RoadsideSOSController::class, 'submit'])->name('sos.submit');
        Route::get('/sos/success', [RoadsideSOSController::class, 'success'])->name('sos.success');
    });

Route::prefix('handyman')
    ->name('handyman.')
    ->middleware('auth')
    ->group(function () {
        // Каталог услуг
        Route::get('/', [HandymanCatalogController::class, 'index'])
            ->name('index');

        // Просмотр конкретной услуги
        Route::get('/service/{slug}', [HandymanCatalogController::class, 'show'])
            ->name('service.show');

        // Страница «Услуга не найдена?»
        Route::get('/custom-request', [HandymanCatalogController::class, 'customRequest'])
            ->name('custom-request');

        // Создание заказа (форма → POST)
        Route::post('/service/{slug}/book', [HandymanBookingController::class, 'store'])
            ->name('service.book');

        Route::post('/custom-request/book', [HandymanBookingController::class, 'storeCustom'])
            ->name('custom.book');
    });

Route::prefix('repair')
    ->name('repair.')
    ->group(function () {
        // Лендинг услуги «Комплексный ремонт»
        Route::get('/', [\App\Http\Controllers\Public\Repair\RepairIntakeController::class, 'index'])
            ->name('index');

        // Форма заявки
        Route::get('/request', [\App\Http\Controllers\Public\Repair\RepairIntakeController::class, 'create'])
            ->name('request')
            ->middleware('auth');

        // Обработка заявки
        Route::post('/request', [\App\Http\Controllers\Public\Repair\RepairIntakeController::class, 'store'])
            ->name('request.store')
            ->middleware('auth');
    });

Route::get('/lang/{locale}', [LanguageController::class, 'switch'])->name('language.switch');
Route::get('/api-info', function () {
    return response()->json([
        'message' => 'GLF Bikube API',
        'status' => 'ok',
        'service' => config('app.name'),
    ]);
})->name('api.info');

Route::get('/order/{orderId}/vipps/fallback', [\App\Http\Controllers\VippsController::class, 'vippsFallback'])
    ->whereNumber('orderId')
    ->name('vipps.fallback');

Route::post('/fast-order', [FastOrderController::class, 'store'])->name('public.fast-order.store');
Route::get('/become-worker', [WorkerOnboardingController::class, 'create']);
Route::post('/become-worker', [WorkerOnboardingController::class, 'store']);

Route::get('/auth/login', function () {
    return view('auth.login-options');
})->name('auth.login-options');

Route::get('/auth/eid/{provider}', [EidLoginController::class, 'redirect'])->name('auth.eid.redirect');
Route::get('/auth/eid/{provider}/callback', [EidLoginController::class, 'callback'])->name('auth.eid.callback');

Route::middleware(['auth', '2fa.confirmed'])
    ->prefix('account')
    ->name('account.')
    ->group(function () {
        Route::get('/', [AccountDashboardController::class, 'index'])->name('dashboard');

        Route::get('/orders', [AccountOrdersController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [AccountOrdersController::class, 'show'])->name('orders.show');
        Route::get('/orders/{order}/track', [OrderTrackerController::class, 'show'])->name('orders.track');
        Route::get('/deliveries', [AccountDeliveryController::class, 'index'])->name('deliveries.index');
        Route::get('/deliveries/new', [AccountDeliveryController::class, 'create'])->name('deliveries.create');
        Route::get('/deliveries/{deliveryOrder}', [AccountDeliveryController::class, 'show'])->name('deliveries.show');

        Route::get('/orders/{order}/claim', [\App\Http\Controllers\Account\OrderClaimController::class, 'create'])->name('orders.claim.create');
        Route::post('/orders/{order}/claim', [\App\Http\Controllers\Account\OrderClaimController::class, 'store'])->name('orders.claim.store');
        Route::get('/orders/{order}/review', [OrderReviewController::class, 'create'])->name('orders.review.create');
        Route::post('/orders/{order}/review', [OrderReviewController::class, 'store'])->name('orders.review.store');
        Route::get('/repairs/{project}', [RepairProjectController::class, 'show'])->name('repairs.show');

        // Claims
        Route::get('/claims', [\App\Http\Controllers\Account\ClaimController::class, 'index'])->name('claims.index');
        Route::get('/claims/{claim}', [\App\Http\Controllers\Account\ClaimController::class, 'show'])->name('claims.show');
        Route::post('/claims/{claim}/messages', [\App\Http\Controllers\Account\ClaimMessageController::class, 'store'])->name('claims.messages.store');

        Route::get('/profile', [AccountProfileController::class, 'edit'])->name('profile.edit');
        Route::post('/profile', [AccountProfileController::class, 'update'])->name('profile.update');

        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');

        Route::get('/notifications/settings', [AccountNotificationsController::class, 'edit'])->name('notifications.edit');
        Route::post('/notifications/settings', [AccountNotificationsController::class, 'update'])->name('notifications.update');

        Route::get('/notifications/feed', [AccountNotificationInboxController::class, 'index'])->name('notifications.feed');
        Route::post('/notifications/feed/mark-read', [AccountNotificationInboxController::class, 'markRead'])->name('notifications.feed.mark-read');
        Route::post('/notifications/feed/mark-all-read', [AccountNotificationInboxController::class, 'markAllRead'])->name('notifications.feed.mark-all-read');

        Route::get('/billing', [AccountBillingController::class, 'index'])->name('billing.index');
        Route::get('/billing/transactions', [AccountBillingController::class, 'transactions'])->name('billing.transactions');

        Route::get('/care', [AccountSocialCareController::class, 'dashboard'])->name('care.dashboard');
        Route::get('/care/visits/{order}', [AccountSocialCareController::class, 'showVisit'])->name('care.visit.show');

        // Errands (Individual Portions)
        Route::get('/errands', [\App\Http\Controllers\Account\ErrandController::class, 'index'])->name('errands.index');
        Route::get('/errands/create', [\App\Http\Controllers\Account\ErrandController::class, 'create'])->name('errands.create');
        Route::post('/errands', [\App\Http\Controllers\Account\ErrandController::class, 'store'])->name('errands.store');
        Route::get('/errands/{errand}', [\App\Http\Controllers\Account\ErrandController::class, 'show'])->name('errands.show');

        Route::post('/switch-client', [ClientContextController::class, 'switch'])->name('client.switch');

        Route::get('/new', [NewOrderController::class, 'index'])->name('new-order.index');
        Route::get('/new/delivery', [NewOrderController::class, 'deliveryForm'])->name('new-order.delivery');
        Route::post('/new/delivery', [NewOrderController::class, 'storeDelivery'])->name('new-order.delivery.store');
        Route::get('/new/eco', [NewOrderController::class, 'ecoForm'])->name('new-order.eco');
        Route::post('/new/eco', [NewOrderController::class, 'storeEco'])->name('new-order.eco.store');
        Route::get('/new/handyman', [NewOrderController::class, 'handymanForm'])->name('new-order.handyman');
        Route::post('/new/handyman', [NewOrderController::class, 'storeHandyman'])->name('new-order.handyman.store');
        Route::get('/new/care', [NewOrderController::class, 'careForm'])->name('new-order.care');
        Route::post('/new/care', [NewOrderController::class, 'storeCare'])->name('new-order.care.store');

        Route::get('/security', [AccountSecurityController::class, 'index'])->name('security.index');
        Route::post('/security/2fa/enable', [AccountSecurityController::class, 'enableTwoFactor'])->name('security.2fa.enable');
        Route::post('/security/2fa/confirm', [AccountSecurityController::class, 'confirmTwoFactor'])->name('security.2fa.confirm');
        Route::post('/security/2fa/disable', [AccountSecurityController::class, 'disableTwoFactor'])->name('security.2fa.disable');

        Route::post('/security/eid/link/{provider}', [AccountSecurityController::class, 'startEidLink'])->name('security.eid.link');
        Route::get('/security/eid/callback/{provider}', [EidLoginController::class, 'callback'])->name('security.eid.callback');
        Route::post('/security/sessions/logout-others', [AccountSecurityController::class, 'logoutOtherSessions'])->name('security.sessions.logout-others');
    });

Route::middleware(['auth'])->prefix('partner')->group(function () {
    Route::get('/', [PartnerPortalWebController::class, 'dashboard']);
    Route::get('/orders', [PartnerPortalWebController::class, 'orders']);
    Route::post('/orders/{order}/status', [PartnerPortalWebController::class, 'updateStatus']);
});

Route::middleware(['auth', 'executor'])
    ->prefix('executor')
    ->name('executor.')
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\Executor\ExecutorDashboardController::class, 'index'])->name('dashboard');
        Route::get('/jobs/{assignment}', [\App\Http\Controllers\Executor\ExecutorDashboardController::class, 'show'])->name('jobs.show');
        Route::post('/jobs/{assignment}/accept', [\App\Http\Controllers\Executor\ExecutorDashboardController::class, 'accept'])->name('jobs.accept');
        Route::post('/jobs/{assignment}/decline', [\App\Http\Controllers\Executor\ExecutorDashboardController::class, 'decline'])->name('jobs.decline');
        Route::post('/jobs/{assignment}/status', [\App\Http\Controllers\Executor\ExecutorDashboardController::class, 'updateStatus'])->name('jobs.status');
    });

Route::get('/dashboard', function () {
    return redirect()->route('account.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Подключение маршрутов модуля Classifieds
$classifiedsWebRoutes = base_path('app/Modules/Classifieds/Routes/web.php');
if (file_exists($classifiedsWebRoutes)) {
    require $classifiedsWebRoutes;
}

// Route::fallback(function () {
//     return redirect()->route('public.catalog.index');
// });

require __DIR__.'/auth.php';

