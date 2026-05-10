<?php

namespace App\Http\Controllers\Account;

use App\Enums\ServiceType;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Presenters\OrderPresenter;
use App\Services\Account\AccountContextManager;
use App\Services\Account\AccountReadService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrdersController extends Controller
{
    public function index(
        Request $request,
        AccountReadService $accountRead,
        AccountContextManager $contextManager
    ): View {
        $user = $request->user();
        $serviceType = $request->string('type')->toString();
        $status = $request->string('status')->toString();
        $activeClient = $contextManager->getActiveClient($user);

        $paginator = $accountRead->getPaginatedOrdersForUserAndClient(
            $user,
            $activeClient,
            $serviceType ?: null,
            $status ?: null
        );

        $paginator->setCollection(
            $paginator->getCollection()->map([OrderPresenter::class, 'forAccount'])
        );

        $serviceTypes = collect(ServiceType::cases())->mapWithKeys(
            fn (ServiceType $type) => [$type->value => $type->label()]
        );

        $statuses = [
            'pending' => 'Ожидает',
            'pending_payment' => 'Ожидает оплаты',
            'payment_pending' => 'Ожидает оплаты',
            'payment_reserved' => 'Оплата зарезервирована',
            'confirmed' => 'Подтверждён',
            'waiting_dispatch' => 'Ожидает диспетчера',
            'assigned' => 'Назначен',
            'worker_accepted' => 'Принят исполнителем',
            'worker_en_route' => 'Исполнитель в пути',
            'at_pickup' => 'На точке забора',
            'picked_up' => 'Забрано',
            'in_progress' => 'В процессе',
            'arrived' => 'Прибыл',
            'completed' => 'Завершён',
            'client_confirmed' => 'Подтверждено клиентом',
            'disputed' => 'Спор',
            'failed' => 'Ошибка',
            'cancelled' => 'Отменён',
        ];

        $collection = $paginator->getCollection();
        $activeOrderCount = $collection
            ->whereIn('status_key', ['pending', 'pending_payment', 'payment_pending', 'confirmed', 'waiting_dispatch', 'assigned', 'worker_accepted', 'worker_en_route', 'at_pickup', 'picked_up', 'in_progress', 'arrived'])
            ->count();
        $historyOrderCount = $collection
            ->whereIn('status_key', ['completed', 'client_confirmed', 'cancelled', 'disputed', 'failed', 'refunded'])
            ->count();

        return view('account.orders.index', [
            'orders' => $paginator,
            'serviceTypes' => $serviceTypes,
            'statuses' => $statuses,
            'activeClient' => $activeClient,
            'activeOrderCount' => $activeOrderCount,
            'historyOrderCount' => $historyOrderCount,
        ]);
    }

    public function show(
        Request $request,
        Order $order,
        AccountReadService $accountRead
    ): View {
        $user = $request->user();

        if (! $accountRead->userCanAccessOrder($user, $order)) {
            abort(403, 'У вас нет доступа к этому заказу');
        }

        $order->load([
            'subOrders',
            'parentOrder',
            'deliveryOrder',
            'deliveryOrder.orderable', // grocery/bulky/food
            'careContext.clientProfile',
            'careContext.trustedContact',
            'careDetails.clientProfile',
            'careDetails.trustedContact',
            'careDetails.careService',
            'careDetails.assignedHelper.user',
            'careDetails.visitReports.helperProfile',
            'handymanDetails',
            'handymanAssignments.executorProfile.user',
            'primaryHandymanAssignment.executorProfile.user',
            'disposalDetails',
            'ecoCertificate',
            'errandDetails',
            'repairProject.stages',
            'repairProject.media',
            'repairProject.updates.author',
            'repairProject.updates.stage',
            'repairProject.projectManager.user',
            'claims',
            'address',
            'geoZone',
        ]);

        $orderCard = OrderPresenter::forAccount($order);

        return view('account.orders.show', compact('order', 'orderCard'));
    }
}
