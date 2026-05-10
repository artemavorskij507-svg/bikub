<?php

use App\Http\Controllers\Api\Courier\CourierDeliveryController;
use App\Http\Controllers\Api\PriceEstimateController;
use App\Http\Controllers\Api\ServiceTypeController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\V1\CheckoutOrderController;
use App\Http\Controllers\Api\V1\PaymentManagementController;
use App\Http\Controllers\IdentityMockController;
use App\Http\Controllers\OrderTrackerController;
use App\Http\Controllers\DispatchController;
use App\Http\Controllers\Api\V1\Account\AccountBillingApiController;
use App\Http\Controllers\Api\V1\Account\AccountDashboardApiController;
use App\Http\Controllers\Api\V1\Account\AccountNotificationsApiController;
use App\Http\Controllers\Api\V1\Account\AccountOrdersApiController;
use App\Http\Controllers\Api\V1\Account\AccountProfileApiController;
use App\Http\Controllers\Api\V1\Account\AccountSocialCareApiController;
use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Central webhook endpoint (public)
Route::post('/webhooks/{provider}', [WebhookController::class, 'receive']);

// Worker status API
Route::middleware('auth:sanctum')->post('/worker/status', [\App\Http\Controllers\Lk\WorkerStatusController::class, 'update']);
Route::middleware('auth:sanctum')->post('/worker/status/toggle', [\App\Http\Controllers\Lk\WorkerStatusController::class, 'toggle']);

/*
|--------------------------------------------------------------------------
| Authenticated Routes (Critical operations)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'throttle:api_critical'])->group(function () {
    // Orders - Create & Update operations
    Route::post('/orders', [App\Http\Controllers\Api\OrderController::class, 'store']);
    Route::patch('/orders/{id}/status', [App\Http\Controllers\Api\OrderController::class, 'updateStatus']);

    // User Profile operations
    Route::patch('/profile', [App\Http\Controllers\Api\V1\Account\AccountProfileApiController::class, 'update']);
    Route::post('/profile/avatar', [App\Http\Controllers\Api\V1\Account\AccountProfileApiController::class, 'updateAvatar']);
});

/*
|--------------------------------------------------------------------------
| Guest Routes (Registration, Password Reset)
|--------------------------------------------------------------------------
*/
Route::middleware(['guest', 'throttle:guest_auth'])->group(function () {
    // Registration
    Route::post('/register', [App\Http\Controllers\Api\AuthController::class, 'register']);

    // Password Reset
    Route::post('/password/forgot', [App\Http\Controllers\Api\AuthController::class, 'forgotPassword']);
    Route::post('/password/reset', [App\Http\Controllers\Api\AuthController::class, 'resetPassword']);
});

// Public API routes
Route::prefix('v1')->group(function () {
    // Health check
    Route::get('/health', function () {
        $checks = [
            'database' => 'unavailable',
            'redis' => 'unavailable',
        ];

        // Check database - using proper method
        try {
            \Illuminate\Support\Facades\DB::statement('SELECT 1');
            $checks['database'] = 'ok';
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Health check: DB unavailable', [
                'error' => $e->getMessage(),
            ]);
            $checks['database'] = 'error';
        }

        // Check Redis - optional service
        try {
            \Illuminate\Support\Facades\Redis::connection()->ping();
            $checks['redis'] = 'ok';
        } catch (\Exception $e) {
            // Redis is optional - not critical
            $checks['redis'] = 'optional';
        }

        // Overall status is ok if database is ok
        $overallStatus = $checks['database'] === 'ok' ? 'ok' : 'degraded';

        return response()->json([
            'status' => $overallStatus,
            'timestamp' => now()->toIso8601String(),
            'service' => 'GLF BiKube API',
            'version' => '1.0.0',
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'checks' => $checks,
        ], $overallStatus === 'ok' ? 200 : 503);
    });

    // Service Categories
    Route::get('/categories', [App\Http\Controllers\Api\ServiceCategoryController::class, 'index']);
    Route::get('/categories/{code}', [App\Http\Controllers\Api\ServiceCategoryController::class, 'show']);

    // Service Types
    Route::get('/service-types', [ServiceTypeController::class, 'index']);
    Route::get('/service-types/{slug}', [ServiceTypeController::class, 'show']);
    Route::get('/service-types/category/{category}', [ServiceTypeController::class, 'byCategory']);

    // Unified Order Engine scenarios and checkout order creation
    Route::get('/order-scenarios', [CheckoutOrderController::class, 'scenarios']);
    Route::post('/checkout/{scenario}/orders', [CheckoutOrderController::class, 'store'])
        ->where('scenario', '[A-Za-z0-9_.-]+')
        ->middleware(['auth:sanctum', 'throttle:orders']);
    Route::get('/orders/{order}/tracker', [OrderTrackerController::class, 'api']);
    Route::post('/payments/{order}/manual-reserve', [PaymentManagementController::class, 'manualReserve'])->middleware(['auth:sanctum']);
    Route::post('/payments/{order}/manual-capture', [PaymentManagementController::class, 'manualCapture'])->middleware(['auth:sanctum']);
    Route::post('/payments/{order}/manual-refund', [PaymentManagementController::class, 'manualRefund'])->middleware(['auth:sanctum']);
    Route::post('/webhooks/vipps-mobilepay', [PaymentManagementController::class, 'vippsWebhook']);
    Route::post('/identity/mock/start', [IdentityMockController::class, 'start'])->middleware(['auth:sanctum']);
    Route::post('/identity/mock/complete', [IdentityMockController::class, 'complete'])->middleware(['auth:sanctum']);

    // Restaurants
    Route::get('/restaurants', [App\Http\Controllers\Api\RestaurantController::class, 'index']);
    Route::get('/restaurants/{slug}', [App\Http\Controllers\Api\RestaurantController::class, 'show']);

    // Products (Marketplace & Cargo)
    Route::get('/products', [App\Http\Controllers\Api\ProductController::class, 'index']);
    Route::get('/products/cargo', [App\Http\Controllers\Api\ProductController::class, 'cargo']);

    // Delivery Price Calculator - with rate limiting
    Route::post('/delivery/calculate-price', [App\Http\Controllers\Api\DeliveryPriceController::class, 'calculate'])->middleware('throttle:price-calculation');

    // Eco Disposal - with rate limiting
    Route::post('/eco/orders', [App\Http\Controllers\Api\EcoOrderController::class, 'store'])->middleware('throttle:orders');
    Route::post('/eco/calculate-price', [App\Http\Controllers\Api\EcoOrderController::class, 'calculatePrice'])->middleware('throttle:price-calculation');

    // Retail Stores
    Route::get('/stores', [App\Http\Controllers\Api\RetailStoreController::class, 'index']);
    Route::get('/stores/{slug}', [App\Http\Controllers\Api\RetailStoreController::class, 'show']);

    // Pricing Rules
    Route::get('/pricing-rules', [App\Http\Controllers\Api\PricingRuleController::class, 'index']);
    Route::get('/pricing-rules/{id}', [App\Http\Controllers\Api\PricingRuleController::class, 'show']);
    Route::post('/price/estimate', PriceEstimateController::class)
        ->middleware('throttle:price-estimate');

    // Geo Zones
    Route::get('/geo/zones', [App\Http\Controllers\Api\GeoZoneController::class, 'index']);
    Route::get('/geo/zones/{slug}', [App\Http\Controllers\Api\GeoZoneController::class, 'show']);
    Route::post('/geo/zone/contains', [App\Http\Controllers\Api\GeoZoneController::class, 'contains'])
        ->middleware('throttle:api');

    // Routing
    Route::post('/route/estimate', [App\Http\Controllers\Api\RouteController::class, 'estimate'])
        ->middleware('throttle:api');
    Route::post('/route/matrix', [App\Http\Controllers\Api\RouteController::class, 'matrix'])
        ->middleware('throttle:api');

    // Legacy geo-zones routes (backward compatibility)
    Route::get('/geo-zones', [App\Http\Controllers\Api\GeoZoneController::class, 'index']);
    Route::get('/geo-zones/{slug}', [App\Http\Controllers\Api\GeoZoneController::class, 'show']);

    // Orders - with rate limiting
    Route::get('/orders', [App\Http\Controllers\Api\OrderController::class, 'index'])->middleware('throttle:api');
    Route::post('/orders', [App\Http\Controllers\Api\OrderController::class, 'store'])->middleware('throttle:orders');
    Route::get('/orders/{id}', [App\Http\Controllers\Api\OrderController::class, 'show'])->middleware('throttle:api');
    Route::patch('/orders/{id}/status', [App\Http\Controllers\Api\OrderController::class, 'updateStatus'])->middleware('throttle:api');
    Route::post('/orders/{id}/payment/intent', [App\Http\Controllers\Api\OrderController::class, 'createPaymentIntent'])->middleware('throttle:payments');
    Route::post('/orders/{id}/payment/confirm', [App\Http\Controllers\Api\OrderController::class, 'confirmPayment'])->middleware('throttle:payments');

    // Quick Order (Fast Order) - with rate limiting
    Route::post('/quick-order', [App\Http\Controllers\Api\QuickOrderController::class, 'store'])->middleware('throttle:orders');

    // Moving module - with rate limiting
    Route::prefix('moving')->name('moving.')->group(function () {
        // Route::post('/orders', [\App\Http\Controllers\Api\MovingOrderController::class, 'store'])->middleware('throttle:orders')->name('orders.store');
        Route::post('/price-estimate', [\App\Http\Controllers\Api\MovingPriceEstimateController::class, 'estimate'])->middleware('throttle:price-calculation')->name('price-estimate');
        Route::post('/photo-estimate', [\App\Http\Controllers\Api\MovingPhotoEstimateController::class, 'estimate'])->middleware('throttle:price-calculation')->name('photo-estimate');
    });

    // Social Care module - with rate limiting
    Route::prefix('care')->name('care.')->group(function () {
        Route::post('/orders', [\App\Http\Controllers\Api\SocialCareOrderController::class, 'store'])->middleware('throttle:orders')->name('orders.store');
    });

    // Errand/Personal Task module - with rate limiting
    Route::prefix('errand')->name('errand.')->group(function () {
        Route::post('/orders', [\App\Http\Controllers\Api\ErrandTaskController::class, 'store'])->middleware('throttle:orders')->name('orders.store');
        Route::post('/price-estimate', [\App\Http\Controllers\Api\ErrandTaskController::class, 'estimate'])->middleware('throttle:price-calculation')->name('price-estimate');
    });

    // Delivery Module
    Route::prefix('delivery')
        ->group(function () {
            Route::middleware('auth:sanctum')->group(function () {
                Route::post('/quick-order', [App\Http\Controllers\Api\Delivery\QuickOrderController::class, 'store']);
                Route::post('/quote', App\Http\Controllers\Api\Delivery\DeliveryQuoteController::class);
                Route::get('/stores', [App\Http\Controllers\Api\Delivery\StoreController::class, 'index']);
                Route::get('/restaurants', [App\Http\Controllers\Api\Delivery\RestaurantController::class, 'index']);
                Route::patch('/orders/{deliveryOrder}/status', [App\Http\Controllers\Api\Delivery\TrackingController::class, 'updateStatus']);
            });

            Route::get('/orders/{deliveryOrder}/tracking', [App\Http\Controllers\Api\Delivery\TrackingController::class, 'show']);
        });

    Route::prefix('courier')
        ->middleware(['auth:sanctum'])
        ->group(function () {
            Route::get('/deliveries', [CourierDeliveryController::class, 'index']);
            Route::get('/deliveries/{deliveryOrder}', [CourierDeliveryController::class, 'show']);
        });

    // Stripe Webhook (no CSRF protection needed, no rate limiting for webhooks)
    Route::post('/stripe/webhook', [App\Http\Controllers\StripeWebhookController::class, 'handle']);

    // Vipps Payment - with rate limiting
    Route::post('/payments/vipps/init', [App\Http\Controllers\VippsController::class, 'initPayment'])->middleware('throttle:payments');
    Route::post('/payments/vipps/callback', [App\Http\Controllers\VippsController::class, 'handleCallback']); // Callbacks should not be throttled
    Route::post('/payments/vipps/webhook', [App\Http\Controllers\VippsController::class, 'handleWebhook']); // Webhooks should not be throttled
    Route::post('/payments/vipps/capture', [App\Http\Controllers\VippsController::class, 'capturePayment'])->middleware('throttle:payments');
    Route::post('/payments/vipps/refund', [App\Http\Controllers\VippsController::class, 'refundPayment'])->middleware('throttle:payments');
    Route::match(['GET', 'POST'], '/payments/vipps/consent-removal', [App\Http\Controllers\VippsController::class, 'handleConsentRemoval']);
    Route::match(['GET', 'POST'], '/payments/vipps/shipping-details', [App\Http\Controllers\VippsController::class, 'getShippingDetails']);

    // Public Storefront
    Route::get('/public/catalog', [App\Http\Controllers\PublicStorefrontController::class, 'getCatalog']);
    Route::get('/public/services/{slug}', [App\Http\Controllers\PublicStorefrontController::class, 'getServiceDetails']);
    Route::post('/public/orders', [App\Http\Controllers\PublicStorefrontController::class, 'createOrder']);
    Route::get('/public/orders/{orderNumber}', [App\Http\Controllers\PublicStorefrontController::class, 'getOrderStatus']);
    Route::get('/public/slots', [App\Http\Controllers\PublicStorefrontController::class, 'getAvailableSlots']);

    // Geo v2 & Routing
    Route::post('/routes/matrix', [App\Http\Controllers\RoutingController::class, 'calculateMatrix']);
    Route::patch('/routes/{id}/recalc-eta', [App\Http\Controllers\RoutingController::class, 'recalculateEta']);

    // SLA Management
    Route::get('/sla/policies', [App\Http\Controllers\SlaController::class, 'getPolicies']);
    Route::post('/sla/policies', [App\Http\Controllers\SlaController::class, 'createPolicy']);
    Route::put('/sla/policies/{id}', [App\Http\Controllers\SlaController::class, 'updatePolicy']);
    Route::post('/sla/calculate', [App\Http\Controllers\SlaController::class, 'calculateSla']);
    Route::get('/sla/orders-at-risk', [App\Http\Controllers\SlaController::class, 'getOrdersAtRisk']);
    Route::get('/sla/alerts', [App\Http\Controllers\SlaController::class, 'generateAlerts']);
    Route::get('/sla/metrics', [App\Http\Controllers\SlaController::class, 'getMetrics']);
    Route::post('/sla/update-policies', [App\Http\Controllers\SlaController::class, 'updatePolicies']);

    // Analytics v2
    Route::prefix('analytics/v2')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\AnalyticsV2Controller::class, 'getDashboardMetrics']);
        Route::get('/sla-breaches', [App\Http\Controllers\AnalyticsV2Controller::class, 'getSlaBreachAnalysis']);
        Route::get('/eta-accuracy', [App\Http\Controllers\AnalyticsV2Controller::class, 'getEtaAccuracy']);
        Route::get('/aht', [App\Http\Controllers\AnalyticsV2Controller::class, 'getAhtMetrics']);
        Route::get('/export', [App\Http\Controllers\AnalyticsV2Controller::class, 'exportData']);
        Route::get('/saved-reports', [App\Http\Controllers\AnalyticsV2Controller::class, 'getSavedReports']);
    });

    // Multi-Tenant & Organizations
    Route::get('/organizations', [App\Http\Controllers\Api\OrganizationController::class, 'index']);
    Route::post('/organizations', [App\Http\Controllers\Api\OrganizationController::class, 'store']);
    Route::get('/organizations/{id}', [App\Http\Controllers\Api\OrganizationController::class, 'show']);
    Route::put('/organizations/{id}', [App\Http\Controllers\Api\OrganizationController::class, 'update']);
    Route::delete('/organizations/{id}', [App\Http\Controllers\Api\OrganizationController::class, 'destroy']);
    Route::post('/organizations/{id}/users', [App\Http\Controllers\Api\OrganizationController::class, 'addUser']);
    Route::delete('/organizations/{id}/users/{userId}', [App\Http\Controllers\Api\OrganizationController::class, 'removeUser']);

    // Search & Facets
    Route::get('/search', [App\Http\Controllers\Api\SearchController::class, 'search']);
    Route::get('/search/autocomplete', [App\Http\Controllers\Api\SearchController::class, 'autocomplete']);
    Route::get('/search/facets', [App\Http\Controllers\Api\SearchController::class, 'getFacets']);
    Route::post('/search/index', [App\Http\Controllers\Api\SearchController::class, 'indexEntity']);
    Route::delete('/search/index', [App\Http\Controllers\Api\SearchController::class, 'removeEntity']);

    // Geo v3 & Advanced Routing
    Route::post('/geo/v3/route', [App\Http\Controllers\Api\GeoV3Controller::class, 'calculateRoute']);
    Route::post('/geo/v3/matrix', [App\Http\Controllers\Api\GeoV3Controller::class, 'calculateMatrix']);
    Route::post('/geo/v3/optimize', [App\Http\Controllers\Api\GeoV3Controller::class, 'optimizeRoute']);
    Route::get('/geo/v3/profile', [App\Http\Controllers\Api\GeoV3Controller::class, 'getOptimalProfile']);
    Route::post('/geo/v3/eta', [App\Http\Controllers\Api\GeoV3Controller::class, 'calculateETA']);
    Route::post('/geo/v3/validate-windows', [App\Http\Controllers\Api\GeoV3Controller::class, 'validateTimeWindows']);

    // Performance & Monitoring
    Route::get('/performance/report', [App\Http\Controllers\Api\PerformanceController::class, 'getReport']);
    Route::get('/performance/slow-queries', [App\Http\Controllers\Api\PerformanceController::class, 'getSlowQueries']);
    Route::post('/performance/optimize-db', [App\Http\Controllers\Api\PerformanceController::class, 'optimizeDatabase']);
    Route::get('/performance/cache-stats', [App\Http\Controllers\Api\PerformanceController::class, 'getCacheStatistics']);
    Route::post('/performance/optimize-cache', [App\Http\Controllers\Api\PerformanceController::class, 'optimizeCache']);
    Route::get('/performance/cost-analysis', [App\Http\Controllers\Api\PerformanceController::class, 'getCostAnalysis']);

    // GDPR & Data Management
    Route::post('/gdpr/request', [App\Http\Controllers\Api\GdprController::class, 'createRequest']);
    Route::get('/gdpr/requests', [App\Http\Controllers\Api\GdprController::class, 'getRequests']);
    Route::get('/gdpr/requests/{id}', [App\Http\Controllers\Api\GdprController::class, 'getRequest']);
    Route::post('/gdpr/requests/{id}/process', [App\Http\Controllers\Api\GdprController::class, 'processRequest']);
    Route::post('/gdpr/anonymize-logs', [App\Http\Controllers\Api\GdprController::class, 'anonymizeLogs']);
    Route::get('/gdpr/retention-report', [App\Http\Controllers\Api\GdprController::class, 'getRetentionReport']);

    // Sprint 5 - Subscriptions & Discounts
    Route::post('/subscriptions/subscribe', [App\Http\Controllers\Api\SubscriptionController::class, 'subscribe']);
    Route::post('/subscriptions/cancel', [App\Http\Controllers\Api\SubscriptionController::class, 'cancel']);
    Route::get('/subscriptions/plans', [App\Http\Controllers\Api\SubscriptionController::class, 'getPlans']);
    Route::get('/subscriptions/my', [App\Http\Controllers\Api\SubscriptionController::class, 'getMySubscriptions']);

    Route::post('/coupons/apply', [App\Http\Controllers\Api\CouponController::class, 'apply']);
    Route::post('/coupons/validate', [App\Http\Controllers\Api\CouponController::class, 'validate']);
    Route::get('/coupons/available', [App\Http\Controllers\Api\CouponController::class, 'getAvailable']);

    Route::post('/bundles/price', [App\Http\Controllers\Api\BundleController::class, 'calculatePrice']);
    Route::get('/bundles/available', [App\Http\Controllers\Api\BundleController::class, 'getAvailable']);

    // Sprint 5 - Returns & Refunds
    Route::post('/orders/{id}/return', [App\Http\Controllers\Api\ReturnController::class, 'createReturn']);
    Route::get('/orders/{id}/returns', [App\Http\Controllers\Api\ReturnController::class, 'getReturns']);
    Route::patch('/returns/{id}/status', [App\Http\Controllers\Api\ReturnController::class, 'updateStatus']);

    Route::post('/payments/{id}/refund', [App\Http\Controllers\Api\RefundController::class, 'createRefund']);
    Route::get('/refunds', [App\Http\Controllers\Api\RefundController::class, 'getRefunds']);

    Route::post('/orders/{id}/sla-credit', [App\Http\Controllers\Api\SlaCreditController::class, 'grantCredit']);
    Route::get('/sla-credits', [App\Http\Controllers\Api\SlaCreditController::class, 'getCredits']);

    // Sprint 5 - Reviews & Disputes
    Route::post('/reviews', [App\Http\Controllers\Api\ReviewController::class, 'createReview']);
    Route::get('/reviews', [App\Http\Controllers\Api\ReviewController::class, 'getReviews']);
    Route::patch('/reviews/{id}/status', [App\Http\Controllers\Api\ReviewController::class, 'updateStatus']);

    Route::post('/disputes', [App\Http\Controllers\Api\DisputeController::class, 'createDispute']);
    Route::get('/disputes', [App\Http\Controllers\Api\DisputeController::class, 'getDisputes']);
    Route::patch('/disputes/{id}/status', [App\Http\Controllers\Api\DisputeController::class, 'updateStatus']);
    Route::post('/disputes/{id}/evidence', [App\Http\Controllers\Api\DisputeController::class, 'addEvidence']);

    // Sprint 5 - CFO Analytics
    Route::get('/cfo/dashboard', [App\Http\Controllers\Api\CfoController::class, 'getDashboard']);
    Route::get('/cfo/revenue', [App\Http\Controllers\Api\CfoController::class, 'getRevenue']);
    Route::get('/cfo/margins', [App\Http\Controllers\Api\CfoController::class, 'getMargins']);
    Route::get('/cfo/ltv-cac', [App\Http\Controllers\Api\CfoController::class, 'getLtvCac']);
    Route::get('/cfo/returns', [App\Http\Controllers\Api\CfoController::class, 'getReturns']);
    Route::get('/cfo/discounts', [App\Http\Controllers\Api\CfoController::class, 'getDiscounts']);

    // Sprint 6 - Multi-Tenant & Search (duplicate removed - see lines 150-163)
    // Sprint 6 - GDPR Tools (duplicate removed - see lines 181-187)

    // Sprint 7 - OAuth2/OIDC
    Route::post('/oauth/token', [App\Http\Controllers\Api\OAuthController::class, 'token']);
    Route::post('/oauth/authorize', [App\Http\Controllers\Api\OAuthController::class, 'authorizeRequest']);
    Route::post('/oauth/revoke', [App\Http\Controllers\Api\OAuthController::class, 'revokeToken']);
    Route::post('/oauth/introspect', [App\Http\Controllers\Api\OAuthController::class, 'introspectToken']);
    Route::post('/oauth/clients', [App\Http\Controllers\Api\OAuthController::class, 'createClient']);

    // Sprint 7 - Partner API v1
    Route::post('/v1/orders', [App\Http\Controllers\Api\PartnerApiController::class, 'createOrder']);
    Route::get('/v1/orders/{id}', [App\Http\Controllers\Api\PartnerApiController::class, 'getOrder']);
    Route::get('/v1/orders/{id}/status', [App\Http\Controllers\Api\PartnerApiController::class, 'getOrderStatus']);
    Route::post('/v1/orders/{id}/cancel', [App\Http\Controllers\Api\PartnerApiController::class, 'cancelOrder']);
    Route::get('/v1/services', [App\Http\Controllers\Api\PartnerApiController::class, 'getServices']);
    Route::get('/v1/zones', [App\Http\Controllers\Api\PartnerApiController::class, 'getZones']);
    Route::get('/v1/slots', [App\Http\Controllers\Api\PartnerApiController::class, 'getAvailableSlots']);

    // Bikube Assistant
    Route::prefix('assistant')->middleware(['auth:sanctum'])->group(function () {
        Route::get('/order/{order}/insights', [\App\Modules\BikubeAssistant\AssistantController::class, 'insights']);

        // Chat API
        Route::post('/conversations', [\App\Http\Controllers\AssistantChatController::class, 'startConversation']);
        Route::post('/conversations/{conversation}/messages', [\App\Http\Controllers\AssistantChatController::class, 'sendMessage']);
        Route::get('/conversations/{conversation}/messages', [\App\Http\Controllers\AssistantChatController::class, 'messages']);
    });

    // Social Care Helper API (v1)
    Route::prefix('helper')
        ->middleware(['auth:sanctum'])
        ->name('helper.')
        ->group(function () {
            Route::get('/me', [\App\Http\Controllers\Api\V1\Helper\HelperMeController::class, 'show'])
                ->name('me');

            Route::get('/visits/upcoming', [\App\Http\Controllers\Api\V1\Helper\HelperVisitsController::class, 'upcoming'])
                ->name('visits.upcoming');

            Route::get('/visits/history', [\App\Http\Controllers\Api\V1\Helper\HelperVisitsController::class, 'history'])
                ->name('visits.history');

            Route::get('/visits/{order}', [\App\Http\Controllers\Api\V1\Helper\HelperVisitsController::class, 'show'])
                ->name('visits.show');

            Route::post('/visits/{order}/accept', [\App\Http\Controllers\Api\V1\Helper\HelperVisitsController::class, 'accept'])
                ->name('visits.accept');

            Route::post('/visits/{order}/en-route', [\App\Http\Controllers\Api\V1\Helper\HelperVisitsController::class, 'markEnRoute'])
                ->name('visits.en_route');

            Route::post('/visits/{order}/start', [\App\Http\Controllers\Api\V1\Helper\HelperVisitsController::class, 'start'])
                ->name('visits.start');

            Route::post('/visits/{order}/finish', [\App\Http\Controllers\Api\V1\Helper\HelperVisitsController::class, 'finish'])
                ->name('visits.finish');

            Route::get('/stats', [\App\Http\Controllers\Api\V1\Helper\HelperStatsController::class, 'stats'])
                ->name('stats');
            Route::post('/emergency', [\App\Http\Controllers\Api\V1\Helper\HelperEmergencyController::class, 'trigger'])
                ->name('emergency.trigger');
        });

    // Sprint 7 - Webhooks
    Route::post('/webhooks/subscriptions', [App\Http\Controllers\Api\WebhookController::class, 'createSubscription']);
    Route::get('/webhooks/subscriptions', [App\Http\Controllers\Api\WebhookController::class, 'getSubscriptions']);
    Route::put('/webhooks/subscriptions/{id}', [App\Http\Controllers\Api\WebhookController::class, 'updateSubscription']);
    Route::delete('/webhooks/subscriptions/{id}', [App\Http\Controllers\Api\WebhookController::class, 'deleteSubscription']);
    Route::get('/webhooks/subscriptions/{id}/logs', [App\Http\Controllers\Api\WebhookController::class, 'getLogs']);

    // Sprint 7 - Dynamic Pricing
    Route::post('/pricing/calculate', [App\Http\Controllers\Api\PricingController::class, 'calculate']);
    Route::get('/pricing/rules', [App\Http\Controllers\Api\PricingController::class, 'index']);
    Route::post('/pricing/rules', [App\Http\Controllers\Api\PricingController::class, 'store']);
    Route::put('/pricing/rules/{id}', [App\Http\Controllers\Api\PricingController::class, 'update']);
    Route::delete('/pricing/rules/{id}', [App\Http\Controllers\Api\PricingController::class, 'destroy']);

    Route::get('/pricing/experiments', [App\Http\Controllers\Api\PricingController::class, 'getExperiments']);
    Route::post('/pricing/experiments', [App\Http\Controllers\Api\PricingController::class, 'createExperiment']);

    // Sprint 7 - Telemetry
    Route::post('/telemetry/events', [App\Http\Controllers\Api\TelemetryController::class, 'processEvents']);
    Route::get('/telemetry/events', [App\Http\Controllers\Api\TelemetryController::class, 'getEvents']);
    Route::post('/telemetry/eta-update', [App\Http\Controllers\Api\TelemetryController::class, 'updateEta']);
    Route::get('/telemetry/anomalies', [App\Http\Controllers\Api\TelemetryController::class, 'getAnomalies']);
    Route::post('/telemetry/route-optimization', [App\Http\Controllers\Api\TelemetryController::class, 'getRouteOptimization']);

    // Operations Core (Unified Dispatch + Live Ops + SLA/Exceptions)
    Route::prefix('operations')->group(function () {
        Route::post('/jobs/normalize', [\App\Http\Controllers\Api\V1\Operations\OperationsJobController::class, 'normalize']);
        Route::post('/dispatch/request', [\App\Http\Controllers\Api\V1\Operations\OperationsDispatchController::class, 'request']);
        Route::post('/dispatch/replan', [\App\Http\Controllers\Api\V1\Operations\OperationsDispatchController::class, 'replan']);
        Route::post('/assignments/{id}/accept', [\App\Http\Controllers\Api\V1\Operations\OperationsAssignmentController::class, 'accept']);
        Route::post('/assignments/{assignment}/reject', [\App\Http\Controllers\Api\Ops\AssignmentController::class, 'reject']);
        Route::post('/assignments/{assignment}/start-travel', [\App\Http\Controllers\Api\Ops\AssignmentController::class, 'startTravel']);
        Route::post('/assignments/{id}/start', [\App\Http\Controllers\Api\V1\Operations\OperationsAssignmentController::class, 'start']);
        Route::post('/assignments/{id}/arrive', [\App\Http\Controllers\Api\V1\Operations\OperationsAssignmentController::class, 'arrive']);
        Route::post('/assignments/{id}/complete', [\App\Http\Controllers\Api\V1\Operations\OperationsAssignmentController::class, 'complete']);
        Route::get('/live/state', [\App\Http\Controllers\Api\V1\Operations\OperationsLiveController::class, 'state']);
        Route::post('/live/executors/{executorId}/location', [\App\Http\Controllers\Api\V1\Operations\OperationsLiveController::class, 'updateLocation']);
        Route::post('/exceptions', [\App\Http\Controllers\Api\V1\Operations\OperationsExceptionController::class, 'open']);
        Route::patch('/exceptions/{id}/ack', [\App\Http\Controllers\Api\V1\Operations\OperationsExceptionController::class, 'ack']);
        Route::patch('/exceptions/{id}/resolve', [\App\Http\Controllers\Api\V1\Operations\OperationsExceptionController::class, 'resolve']);
        Route::post('/sla/evaluate', [\App\Http\Controllers\Api\V1\Operations\OperationsSlaController::class, 'evaluate']);
    });

    // Legacy dispatch endpoints used by existing Filament page
    Route::prefix('dispatch')->group(function () {
        Route::get('/state', [DispatchController::class, 'getState']);
        Route::patch('/orders/{id}/status', [DispatchController::class, 'updateOrderStatus']);
        Route::patch('/tasks/{taskId}/assign', [DispatchController::class, 'assignTask']);
        Route::get('/winter-protocol', [DispatchController::class, 'getWinterProtocol']);
        Route::post('/winter-protocol', [DispatchController::class, 'updateWinterProtocol']);
    });

    // Sprint 7 - Geofences
    Route::get('/geofences', [App\Http\Controllers\Api\GeofenceController::class, 'getGeofences']);
    Route::post('/geofences', [App\Http\Controllers\Api\GeofenceController::class, 'createGeofence']);
    Route::put('/geofences/{id}', [App\Http\Controllers\Api\GeofenceController::class, 'updateGeofence']);
    Route::delete('/geofences/{id}', [App\Http\Controllers\Api\GeofenceController::class, 'deleteGeofence']);
    Route::get('/geofences/{id}/events', [App\Http\Controllers\Api\GeofenceController::class, 'getEvents']);

    // Sprint 7 - KYC & Onboarding
    Route::post('/kyc/documents', [App\Http\Controllers\Api\KycController::class, 'uploadDocument']);
    Route::get('/kyc/documents', [App\Http\Controllers\Api\KycController::class, 'getDocuments']);
    Route::get('/kyc/status', [App\Http\Controllers\Api\KycController::class, 'getStatus']);
    Route::post('/contracts/sign', [App\Http\Controllers\Api\ContractController::class, 'signContract']);
    Route::get('/contracts', [App\Http\Controllers\Api\ContractController::class, 'getContracts']);
    Route::get('/onboarding/checklist', [App\Http\Controllers\Api\OnboardingController::class, 'getChecklist']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [App\Http\Controllers\Api\AuthController::class, 'logout']);
        Route::get('/me', [App\Http\Controllers\Api\AuthController::class, 'me']);

        // Push Notifications
        Route::post('/push/subscribe', [App\Http\Controllers\Api\PushNotificationController::class, 'subscribe']);
        Route::post('/push/unsubscribe', [App\Http\Controllers\Api\PushNotificationController::class, 'unsubscribe']);
        Route::get('/push/subscriptions', [App\Http\Controllers\Api\PushNotificationController::class, 'subscriptions']);

        // Partner Portal
        Route::get('/partner/me', [App\Http\Controllers\PartnerPortalController::class, 'getProfile']);
        Route::get('/partner/settings', [App\Http\Controllers\PartnerPortalController::class, 'getSettings']);
        Route::put('/partner/settings', [App\Http\Controllers\PartnerPortalController::class, 'updateSettings']);
        Route::get('/partner/service-areas', [App\Http\Controllers\PartnerPortalController::class, 'getServiceAreas']);
        Route::post('/partner/service-areas', [App\Http\Controllers\PartnerPortalController::class, 'updateServiceArea']);
        Route::get('/partner/pricing-overrides', [App\Http\Controllers\PartnerPortalController::class, 'getPricingOverrides']);
        Route::post('/partner/pricing-overrides', [App\Http\Controllers\PartnerPortalController::class, 'createPricingOverride']);
        Route::get('/partner/statements', [App\Http\Controllers\PartnerPortalController::class, 'getStatements']);
        Route::post('/partner/statements/generate', [App\Http\Controllers\PartnerPortalController::class, 'generateStatement']);

        // Notification Preferences
        Route::get('/notify/preferences', [App\Http\Controllers\NotificationController::class, 'getPreferences']);
        Route::put('/notify/preferences', [App\Http\Controllers\NotificationController::class, 'updatePreferences']);
        Route::post('/notify/send-test', [App\Http\Controllers\NotificationController::class, 'sendTest']);
        Route::get('/notify/history', [App\Http\Controllers\NotificationController::class, 'getHistory']);

        // Mobile PWA (Courier)
        Route::get('/mobile/tasks', [App\Http\Controllers\MobileController::class, 'getTasks']);
        Route::post('/mobile/tasks/{id}/checkin', [App\Http\Controllers\MobileController::class, 'checkIn']);
        Route::post('/mobile/tasks/{id}/proofs', [App\Http\Controllers\MobileController::class, 'uploadProofs']);
        Route::post('/mobile/tasks/{id}/complete', [App\Http\Controllers\MobileController::class, 'completeTask']);
        Route::post('/mobile/devices/register', [App\Http\Controllers\MobileController::class, 'registerDevice']);
        Route::get('/mobile/offline-data', [App\Http\Controllers\MobileController::class, 'getOfflineData']);
        Route::post('/mobile/sync', [App\Http\Controllers\MobileController::class, 'syncOfflineData']);
    });

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/account/dashboard', [AccountDashboardApiController::class, 'show'])
            ->name('api.v1.account.dashboard');

        Route::get('/account/orders', [AccountOrdersApiController::class, 'index'])
            ->name('api.v1.account.orders.index');

        Route::get('/account/orders/{order}', [AccountOrdersApiController::class, 'show'])
            ->name('api.v1.account.orders.show');

        Route::get('/account/care', [AccountSocialCareApiController::class, 'index'])
            ->name('api.v1.account.care.index');

        Route::get('/account/care/visits/{order}', [AccountSocialCareApiController::class, 'showVisit'])
            ->name('api.v1.account.care.visit.show');

        Route::get('/account/profile', [AccountProfileApiController::class, 'show'])
            ->name('api.v1.account.profile.show');

        Route::get('/account/notification-settings', [AccountNotificationsApiController::class, 'show'])
            ->name('api.v1.account.notifications.show');
        Route::get('/account/notifications/list', [AccountNotificationsApiController::class, 'index'])
            ->name('api.v1.account.notifications.index');
        Route::post('/account/notifications/mark-read', [AccountNotificationsApiController::class, 'markRead'])
            ->name('api.v1.account.notifications.mark-read');
        Route::post('/account/notifications/mark-all-read', [AccountNotificationsApiController::class, 'markAllRead'])
            ->name('api.v1.account.notifications.mark-all-read');
        Route::get('/account/timeline', [AccountNotificationsApiController::class, 'timeline'])
            ->name('api.v1.account.timeline');

        Route::get('/account/billing/summary', [AccountBillingApiController::class, 'summary'])
            ->name('api.v1.account.billing.summary');
        Route::get('/account/billing/transactions', [AccountBillingApiController::class, 'transactions'])
            ->name('api.v1.account.billing.transactions');
    });

    // Executor API (v1)
    Route::prefix('executor')
        ->middleware(['auth:sanctum', 'executor'])
        ->group(function () {
            // Профиль исполнителя
            Route::get('/me', [\App\Http\Controllers\Api\V1\Executor\ExecutorProfileController::class, 'me']);

            // Список задач/заказов мастера
            Route::get('/jobs', [\App\Http\Controllers\Api\V1\Executor\ExecutorJobsController::class, 'index']);
            Route::get('/jobs/{assignment}', [\App\Http\Controllers\Api\V1\Executor\ExecutorJobsController::class, 'show'])
                ->whereNumber('assignment');

            // Действия по назначению
            Route::post('/jobs/{assignment}/accept', [\App\Http\Controllers\Api\V1\Executor\ExecutorJobsController::class, 'accept']);
            Route::post('/jobs/{assignment}/decline', [\App\Http\Controllers\Api\V1\Executor\ExecutorJobsController::class, 'decline']);
            Route::post('/jobs/{assignment}/status', [\App\Http\Controllers\Api\V1\Executor\ExecutorJobsController::class, 'updateStatus']);

            // Материалы
            Route::post('/jobs/{assignment}/materials', [\App\Http\Controllers\Api\V1\Executor\ExecutorJobsController::class, 'addMaterials']);
        });

    Route::get('/tasks', [TaskController::class, 'index']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::patch('/tasks/{id}', [TaskController::class, 'update']);
    Route::post('/tasks/{id}/events', [TaskController::class, 'storeEvent']);
});

/*
|--------------------------------------------------------------------------
| Module Routes
|--------------------------------------------------------------------------
*/
/*
|--------------------------------------------------------------------------
| Loyalty System Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'throttle:api_critical'])->group(function () {
    Route::get('/loyalty/balance', [\App\Http\Controllers\Api\LoyaltyController::class, 'index']);
    Route::get('/loyalty/transactions', [\App\Http\Controllers\Api\LoyaltyController::class, 'show']);
    Route::post('/loyalty/redeem', [\App\Http\Controllers\Api\LoyaltyController::class, 'store']);
});

$classifiedsRoutes = base_path('app/Modules/Classifieds/Routes/api.php');
if (file_exists($classifiedsRoutes)) {
    require $classifiedsRoutes;
}

$opsRoutes = base_path('routes/api_ops.php');
if (file_exists($opsRoutes)) {
    require $opsRoutes;
}

$agencyRoutes = base_path('routes/api_agency_agents.php');
if (file_exists($agencyRoutes)) {
    require $agencyRoutes;
}
