<?php

namespace App\Http\Controllers\Account;

use App\Enums\ServiceType;
use App\Http\Controllers\Controller;
use App\Models\ErrandOrderDetails;
use App\Models\Order;
use App\Services\Errand\ErrandPricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ErrandController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $errands = ErrandOrderDetails::query()
            ->whereHas('order', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->with('order')
            ->latest('created_at')
            ->paginate(15);

        return view('account.errands.index', compact('errands'));
    }

    public function create()
    {
        return view('account.errands.create');
    }

    public function store(Request $request, ErrandPricingService $pricingService)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'description' => ['required', 'string', 'max:5000'],
            'category' => ['nullable', 'string', 'max:64'],
            'from_address' => ['nullable', 'string', 'max:255'],
            'to_address' => ['nullable', 'string', 'max:255'],
            'desired_start_at' => ['nullable', 'date'],
            'is_urgent' => ['sometimes', 'boolean'],
            'requires_trusted_helper' => ['sometimes', 'boolean'],
            'requires_signature' => ['sometimes', 'boolean'],
            'involves_documents' => ['sometimes', 'boolean'],
            'expected_duration_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'complexity_level' => ['nullable', 'integer', 'min:1', 'max:5'],
            'material_advance_amount' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['is_urgent'] = (bool) ($validated['is_urgent'] ?? false);
        $validated['requires_trusted_helper'] = (bool) ($validated['requires_trusted_helper'] ?? false);
        $validated['requires_signature'] = (bool) ($validated['requires_signature'] ?? false);
        $validated['involves_documents'] = (bool) ($validated['involves_documents'] ?? false);

        // дефолты
        $validated['complexity_level'] = $validated['complexity_level'] ?? 2;
        $validated['expected_duration_minutes'] = $validated['expected_duration_minutes'] ?? 60;
        $validated['material_advance_amount'] = $validated['material_advance_amount'] ?? 0;

        // Конвертируем material_advance_amount из kr в minor units (умножаем на 100)
        if (isset($validated['material_advance_amount']) && $validated['material_advance_amount'] > 0) {
            $validated['material_advance_amount'] = (int) ($validated['material_advance_amount'] * 100);
        }

        $errandDetails = null;

        DB::transaction(function () use ($user, $validated, &$errandDetails, $pricingService) {
            // 1) создаём заказ
            $order = new Order;
            $order->user_id = $user->id;
            $order->service_type = ServiceType::ERRAND->value;
            $order->status = 'pending';
            $order->estimated_total = null; // заполним ниже
            $order->save();

            // 2) создаём детали поручения
            $errandDetails = new ErrandOrderDetails;
            $errandDetails->order_id = $order->id;
            $errandDetails->category = $validated['category'] ?? null;
            $errandDetails->description = $validated['description'];
            $errandDetails->from_address = $validated['from_address'] ?? null;
            $errandDetails->to_address = $validated['to_address'] ?? null;
            $errandDetails->desired_start_at = $validated['desired_start_at'] ?? null;
            $errandDetails->expected_duration_minutes = $validated['expected_duration_minutes'];
            $errandDetails->complexity_level = $validated['complexity_level'];
            $errandDetails->material_advance_amount = $validated['material_advance_amount'];
            $errandDetails->is_urgent = $validated['is_urgent'];
            $errandDetails->requires_trusted_helper = $validated['requires_trusted_helper'];
            $errandDetails->requires_signature = $validated['requires_signature'];
            $errandDetails->involves_documents = $validated['involves_documents'];
            $errandDetails->meta = [
                // тут позже можно хранить distance_km и другую служебную инфу
            ];
            $errandDetails->save();

            // 3) первичный расчёт стоимости
            // TODO: позже сюда придёт реальное расстояние из маршрутизатора (Mapbox/OSRM)
            $distanceKm = 0.0;

            $pricingService->estimateAndFill($errandDetails, $distanceKm);
            $errandDetails->save();

            // 4) синхронизируем оценку в Order
            $order->estimated_total = $errandDetails->total_estimated_price;
            $order->save();
        });

        return redirect()
            ->route('account.errands.show', $errandDetails)
            ->with('status', 'Поручение создано. Наш диспетчер рассмотрит задачу и назначит исполнителя.');
    }

    public function show(ErrandOrderDetails $errand)
    {
        $user = Auth::user();

        // защита: клиент должен видеть только свои поручения
        if ($errand->order->user_id !== $user->id) {
            abort(403);
        }

        $errand->load('order');

        return view('account.errands.show', compact('errand'));
    }
}
