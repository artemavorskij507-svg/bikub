<?php

namespace App\Http\Controllers\Account;

use App\Enums\ServiceType;
use App\Http\Controllers\Controller;
use App\Models\CareOrderDetails;
use App\Models\CareService;
use App\Models\ClientProfile;
use App\Models\Order;
use App\Models\Store;
use App\Services\Account\AccountContextManager;
use App\Services\Notifications\NotificationFeedService;
use App\Services\SocialCare\CareAccountReadService;
use App\Services\SocialCare\CareContactResolver;
use App\Services\SocialCare\SocialCareIntegrationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewOrderController extends Controller
{
    public function __construct(
        protected NotificationFeedService $feedService
    ) {}

    public function index(
        AccountContextManager $contextManager,
        CareAccountReadService $careRead
    ): View {
        $user = auth()->user();

        return view('account.new.index', [
            'activeClient' => $contextManager->getActiveClient($user),
            'hasSocialCareAccess' => $careRead->userHasAnyCareRelation($user),
        ]);
    }

    public function deliveryForm(
        AccountContextManager $contextManager
    ): View {
        $user = auth()->user();

        return view('account.new.delivery', [
            'activeClient' => $contextManager->getActiveClient($user),
            'stores' => Store::query()->orderBy('name')->get(),
        ]);
    }

    public function storeDelivery(
        Request $request,
        AccountContextManager $contextManager,
        CareContactResolver $contactResolver,
        SocialCareIntegrationService $careIntegration
    ): RedirectResponse {
        $user = auth()->user();
        $client = $contextManager->getActiveClient($user);

        $data = $request->validate([
            'store_id' => ['required', 'exists:stores,id'],
            'address' => ['required', 'string', 'max:255'],
            'scheduled_at' => ['nullable', 'date'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'is_vulnerable_client' => ['nullable', 'boolean'],
        ]);

        $scheduledAt = $data['scheduled_at'] ?? null;

        $order = Order::create([
            'user_id' => $user->id,
            'store_id' => (int) $data['store_id'],
            'status' => 'pending',
            'priority' => 'normal',
            'service_type' => ServiceType::GROCERY_DELIVERY->value,
            'scheduled_at' => $scheduledAt ? (is_string($scheduledAt) ? Carbon::parse($scheduledAt) : ($scheduledAt instanceof \Carbon\Carbon ? $scheduledAt : null)) : null,
            'notes' => $data['comment'] ?? null,
            'metadata' => [
                'source' => 'account_portal',
                'flow' => 'delivery',
                'address' => $data['address'],
            ],
        ]);

        if ($client || ! empty($data['is_vulnerable_client'])) {
            $trustedContact = $contactResolver->resolveTrustedContactFor($user, $client);
            if ($client) {
                $careIntegration->ensureCareContextForOrder(
                    $order,
                    $client,
                    $trustedContact,
                    $user,
                    $data['comment'] ?? null
                );
            }
        }

        $this->pushOrderNotification($user, $order, 'Создан заказ на доставку');

        return redirect()
            ->route('account.orders.show', $order)
            ->with('status', 'Заказ на доставку создан');
    }

    public function ecoForm(AccountContextManager $contextManager): View
    {
        $user = auth()->user();

        return view('account.new.eco', [
            'activeClient' => $contextManager->getActiveClient($user),
        ]);
    }

    public function storeEco(
        Request $request,
        AccountContextManager $contextManager,
        CareContactResolver $contactResolver,
        SocialCareIntegrationService $careIntegration
    ): RedirectResponse {
        $user = auth()->user();
        $client = $contextManager->getActiveClient($user);

        $data = $request->validate([
            'address' => ['required', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'scheduled_at' => ['nullable', 'date'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $scheduledAt = $data['scheduled_at'] ?? null;

        $order = Order::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'priority' => 'normal',
            'service_type' => ServiceType::ECO_DISPOSAL->value,
            'scheduled_at' => $scheduledAt ? (is_string($scheduledAt) ? Carbon::parse($scheduledAt) : ($scheduledAt instanceof \Carbon\Carbon ? $scheduledAt : null)) : null,
            'notes' => $data['comment'] ?? null,
            'metadata' => [
                'source' => 'account_portal',
                'flow' => 'eco',
                'address' => $data['address'],
                'items' => $data['items'],
            ],
        ]);

        if ($client) {
            $trustedContact = $contactResolver->resolveTrustedContactFor($user, $client);
            $careIntegration->ensureCareContextForOrder(
                $order,
                $client,
                $trustedContact,
                $user,
                $data['comment'] ?? null
            );
        }

        $this->pushOrderNotification($user, $order, 'Создан эко-вывоз');

        return redirect()
            ->route('account.orders.show', $order)
            ->with('status', 'Заявка на эко-вывоз создана');
    }

    public function handymanForm(AccountContextManager $contextManager): View
    {
        $user = auth()->user();

        return view('account.new.handyman', [
            'activeClient' => $contextManager->getActiveClient($user),
        ]);
    }

    public function storeHandyman(
        Request $request,
        AccountContextManager $contextManager,
        CareContactResolver $contactResolver,
        SocialCareIntegrationService $careIntegration
    ): RedirectResponse {
        $user = auth()->user();
        $client = $contextManager->getActiveClient($user);

        $data = $request->validate([
            'address' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'scheduled_at' => ['nullable', 'date'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $scheduledAt = $data['scheduled_at'] ?? null;

        $order = Order::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'priority' => 'normal',
            'service_type' => ServiceType::HANDYMAN_HOURLY->value,
            'scheduled_at' => $scheduledAt ? (is_string($scheduledAt) ? Carbon::parse($scheduledAt) : ($scheduledAt instanceof \Carbon\Carbon ? $scheduledAt : null)) : null,
            'notes' => $data['comment'] ?? null,
            'metadata' => [
                'source' => 'account_portal',
                'flow' => 'handyman',
                'address' => $data['address'],
                'description' => $data['description'],
            ],
        ]);

        if ($client) {
            $trustedContact = $contactResolver->resolveTrustedContactFor($user, $client);
            $careIntegration->ensureCareContextForOrder(
                $order,
                $client,
                $trustedContact,
                $user,
                $data['comment'] ?? null
            );
        }

        $this->pushOrderNotification($user, $order, 'Создан заказ мастеру');

        return redirect()
            ->route('account.orders.show', $order)
            ->with('status', 'Заявка мастеру создана');
    }

    public function careForm(
        AccountContextManager $contextManager,
        CareAccountReadService $careRead
    ): View {
        $user = auth()->user();

        $clients = $careRead->getClientsForUser($user);
        $careServices = CareService::query()->where('is_active', true)->orderBy('name')->get();

        // Если нет клиентов или услуг - показываем информационное сообщение вместо 403
        if ($clients->isEmpty()) {
            return view('account.new.care', [
                'activeClient' => $contextManager->getActiveClient($user),
                'clients' => collect(),
                'careServices' => $careServices,
                'error' => 'У вас нет доступа к профилям клиентов для социальной помощи. Обратитесь к администратору.',
            ]);
        }

        if ($careServices->isEmpty()) {
            return view('account.new.care', [
                'activeClient' => $contextManager->getActiveClient($user),
                'clients' => $clients,
                'careServices' => collect(),
                'error' => 'В данный момент нет доступных услуг социальной помощи.',
            ]);
        }

        return view('account.new.care', [
            'activeClient' => $contextManager->getActiveClient($user),
            'clients' => $clients,
            'careServices' => $careServices,
        ]);
    }

    public function storeCare(
        Request $request,
        CareAccountReadService $careRead,
        CareContactResolver $contactResolver,
        SocialCareIntegrationService $careIntegration
    ): RedirectResponse {
        $user = auth()->user();

        $data = $request->validate([
            'client_profile_id' => ['required', 'integer'],
            'care_service_id' => ['required', 'exists:care_services,id'],
            'scheduled_start_at' => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:30', 'max:600'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $clients = $careRead->getClientsForUser($user);
        /** @var ClientProfile|null $client */
        $client = $clients->firstWhere('id', (int) $data['client_profile_id']);

        if (! $client) {
            abort(403);
        }

        $careService = CareService::findOrFail($data['care_service_id']);
        try {
            $scheduledStart = is_string($data['scheduled_start_at'])
                ? Carbon::parse($data['scheduled_start_at'])
                : ($data['scheduled_start_at'] instanceof \Carbon\Carbon
                    ? $data['scheduled_start_at']
                    : null);
            if (! $scheduledStart) {
                throw new \InvalidArgumentException('Invalid scheduled_start_at value');
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to parse scheduled_start_at in NewOrderController', [
                'data' => $data['scheduled_start_at'] ?? null,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
        $duration = $data['duration_minutes'] ?? $careService->base_duration_minutes ?? 60;

        $order = Order::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'priority' => 'normal',
            'service_type' => ServiceType::SOCIAL_CARE_VISIT->value,
            'scheduled_at' => $scheduledStart,
            'notes' => $data['comment'] ?? null,
            'metadata' => [
                'source' => 'account_portal',
                'flow' => 'social_care',
                'care_service_id' => $careService->id,
            ],
        ]);

        $trustedContact = $contactResolver->resolveTrustedContactFor($user, $client);

        $careDetails = CareOrderDetails::create([
            'order_id' => $order->id,
            'client_profile_id' => $client->id,
            'trusted_contact_id' => $trustedContact?->id,
            'care_service_id' => $careService->id,
            'scheduled_start_at' => $scheduledStart,
            'scheduled_end_at' => $scheduledStart->copy()->addMinutes($duration),
            'care_status' => 'SCHEDULED',
            'notes_for_helper' => $data['comment'] ?? null,
        ]);

        $careIntegration->ensureCareContextForOrder(
            $order,
            $client,
            $trustedContact,
            $user,
            $data['comment'] ?? null
        );

        $this->feedService->push(
            $user,
            'social_care.visit_requested',
            'social_care',
            'Создан запрос на социальный визит',
            $client ? "Для {$client->full_name}" : null,
            $order,
            [
                'order_id' => $order->id,
                'care_order_details_id' => $careDetails->id,
            ]
        );

        $this->feedService->push(
            $user,
            'billing.charge_captured',
            'billing',
            "Создан заказ #{$order->id}",
            'Заказ внесён в биллинг (сумма будет рассчитана после исполнения).',
            $order
        );

        return redirect()
            ->route('account.care.visit.show', $order)
            ->with('status', 'Запрос на социальный визит создан');
    }

    protected function pushOrderNotification($user, Order $order, string $title): void
    {
        $this->feedService->push(
            $user,
            'order.created',
            'order',
            $title,
            null,
            $order,
            [
                'order_id' => $order->id,
                'service_type' => $order->service_type,
            ]
        );
    }
}
