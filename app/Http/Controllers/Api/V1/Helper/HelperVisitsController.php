<?php

namespace App\Http\Controllers\Api\V1\Helper;

use App\Http\Controllers\Api\V1\Helper\Concerns\ResolvesHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Helper\FinishVisitRequest;
use App\Models\CareOrderDetails;
use App\Models\Order;
use App\Services\SocialCare\VisitLifecycleService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HelperVisitsController extends Controller
{
    use ResolvesHelper;

    private const FINAL_STATUSES = [
        'COMPLETED',
        'CANCELLED',
        'CANCELLED_BY_CLIENT',
        'CANCELLED_BY_OPERATOR',
        'CANCELLED_BY_TRUSTED_CONTACT',
        'NO_SHOW',
    ];

    private const UPCOMING_STATUSES = [
        'PENDING',
        'SCHEDULED',
        'ACCEPTED_BY_HELPER',
        'EN_ROUTE',
        'IN_PROGRESS',
    ];

    public function __construct(
        private VisitLifecycleService $visitLifecycleService
    ) {}

    public function upcoming(Request $request): JsonResponse
    {
        $helper = $this->helperProfile();

        $query = CareOrderDetails::query()
            ->with(['order', 'clientProfile', 'careService', 'assignedHelper'])
            ->where('assigned_helper_id', $helper->id)
            ->whereIn('care_status', self::UPCOMING_STATUSES)
            ->where(function ($q) {
                $q->whereNull('scheduled_start_at')
                    ->orWhere('scheduled_start_at', '>=', now()->subMinutes(120));
            })
            ->orderBy('scheduled_start_at');

        $visits = $query->paginate(20);

        return response()->json([
            'data' => $visits->map(function (CareOrderDetails $visit) {
                return $this->formatVisitResponse($visit);
            }),
            'meta' => [
                'current_page' => $visits->currentPage(),
                'last_page' => $visits->lastPage(),
                'per_page' => $visits->perPage(),
                'total' => $visits->total(),
            ],
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $helper = $this->helperProfile();

        $query = CareOrderDetails::query()
            ->with(['order', 'clientProfile', 'careService', 'visitReports'])
            ->where('assigned_helper_id', $helper->id)
            ->whereIn('care_status', self::FINAL_STATUSES)
            ->orderByDesc('scheduled_start_at');

        $visits = $query->paginate(20);

        return response()->json([
            'data' => $visits->map(function (CareOrderDetails $visit) {
                return $this->formatVisitResponse($visit, true);
            }),
            'meta' => [
                'current_page' => $visits->currentPage(),
                'last_page' => $visits->lastPage(),
                'per_page' => $visits->perPage(),
                'total' => $visits->total(),
            ],
        ]);
    }

    public function show(Order $order): JsonResponse
    {
        $this->ensureSocialCareOrder($order);

        $helper = $this->helperProfile();

        $order->load([
            'careDetails.clientProfile',
            'careDetails.careService',
            'careDetails.visitReports.helperProfile',
        ]);

        $careDetails = $order->careDetails;

        if (! $careDetails) {
            abort(404, 'Care order details not found');
        }

        if ($careDetails->assigned_helper_id !== $helper->id) {
            abort(403, 'Visit not assigned to this helper');
        }

        return response()->json([
            'data' => $this->formatVisitResponse($careDetails, true),
        ]);
    }

    public function accept(Order $order): JsonResponse
    {
        $this->ensureSocialCareOrder($order);

        $helper = $this->helperProfile();

        $updatedOrder = $this->visitLifecycleService->helperAccept($order, $helper);

        return response()->json([
            'data' => [
                'order_id' => $updatedOrder->id,
                'care_status' => $updatedOrder->careDetails->care_status,
            ],
        ]);
    }

    public function markEnRoute(Order $order): JsonResponse
    {
        $this->ensureSocialCareOrder($order);

        $helper = $this->helperProfile();

        $updatedOrder = $this->visitLifecycleService->markEnRoute($order, $helper);

        return response()->json([
            'data' => [
                'order_id' => $updatedOrder->id,
                'care_status' => $updatedOrder->careDetails->care_status,
            ],
        ]);
    }

    public function start(Request $request, Order $order): JsonResponse
    {
        $this->ensureSocialCareOrder($order);

        $helper = $this->helperProfile();

        $startedAt = $request->input('started_at')
            ? Carbon::parse($request->input('started_at'))
            : null;

        $updatedOrder = $this->visitLifecycleService->startVisit($order, $helper, $startedAt);

        return response()->json([
            'data' => [
                'order_id' => $updatedOrder->id,
                'care_status' => $updatedOrder->careDetails->care_status,
                'started_at' => $updatedOrder->started_at?->toIso8601String(),
            ],
        ]);
    }

    public function finish(FinishVisitRequest $request, Order $order): JsonResponse
    {
        $this->ensureSocialCareOrder($order);

        $helper = $this->helperProfile();

        $endedAt = $request->input('ended_at')
            ? Carbon::parse($request->input('ended_at'))
            : now();

        $reportData = [
            'started_at' => $request->input('started_at') ? Carbon::parse($request->input('started_at')) : null,
            'ended_at' => $endedAt,
            'status' => $request->input('status'),
            'summary' => $request->input('summary'),
            'client_mood' => $request->input('client_mood'),
            'issues_noted' => $request->input('issues_noted'),
            'followup_recommended' => $request->boolean('followup_recommended'),
            'followup_notes' => $request->input('followup_notes'),
        ];

        $updatedOrder = $this->visitLifecycleService->finishVisit($order, $helper, $endedAt, $reportData);

        return response()->json([
            'data' => [
                'order_id' => $updatedOrder->id,
                'care_status' => $updatedOrder->careDetails->care_status,
                'visit_report' => $updatedOrder->careDetails->visitReports->last(),
            ],
        ]);
    }

    protected function ensureSocialCareOrder(Order $order): void
    {
        $order->load('careDetails');

        if (! $order->careDetails) {
            abort(404, 'Not a social care order');
        }

        if (! $order->isSocialCare()) {
            abort(400, 'Order is not a social care visit');
        }
    }

    protected function formatVisitResponse(CareOrderDetails $visit, bool $includeReport = false): array
    {
        $data = [
            'id' => $visit->id,
            'order_id' => $visit->order_id,
            'care_status' => $visit->care_status,
            'scheduled_start_at' => $visit->scheduled_start_at?->toIso8601String(),
            'scheduled_end_at' => $visit->scheduled_end_at?->toIso8601String(),
            'client' => $visit->clientProfile ? [
                'id' => $visit->clientProfile->id,
                'full_name' => $visit->clientProfile->full_name,
                'address_line' => $visit->clientProfile->address_line,
                'postal_code' => $visit->clientProfile->postal_code,
                'city' => $visit->clientProfile->city,
                'phone' => $visit->clientProfile->phone,
                'mobility_notes' => $visit->clientProfile->mobility_notes,
            ] : null,
            'care_service' => $visit->careService ? [
                'id' => $visit->careService->id,
                'name' => $visit->careService->name,
                'description' => $visit->careService->description,
            ] : null,
            'notes_for_helper' => $visit->notes_for_helper,
        ];

        if ($includeReport && $visit->relationLoaded('visitReports')) {
            $latestReport = $visit->visitReports->sortByDesc('created_at')->first();
            if ($latestReport) {
                $data['visit_report'] = [
                    'id' => $latestReport->id,
                    'started_at' => $latestReport->started_at?->toIso8601String(),
                    'ended_at' => $latestReport->ended_at?->toIso8601String(),
                    'status' => $latestReport->status,
                    'summary' => $latestReport->summary,
                    'client_mood' => $latestReport->client_mood,
                    'issues_noted' => $latestReport->issues_noted,
                    'followup_recommended' => $latestReport->followup_recommended,
                    'followup_notes' => $latestReport->followup_notes,
                ];
            }
        }

        return $data;
    }
}
