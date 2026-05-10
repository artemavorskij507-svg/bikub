<?php

namespace App\Http\Controllers\Public\Repair;

use App\Enums\ServiceType;
use App\Events\RepairProjectCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\Repair\RepairIntakeRequest;
use App\Models\Order;
use App\Models\RepairProject;
use App\Services\Notifications\NotificationFeedService;
use Illuminate\Support\Facades\DB;

class RepairIntakeController extends Controller
{
    public function __construct(
        protected NotificationFeedService $feedService
    ) {}

    public function index()
    {
        // Лендинг: описание услуги, преимущества, CTA «Оставить заявку»
        return view('public.repair.index');
    }

    public function create()
    {
        // Страница с расширенной формой заявки
        return view('public.repair.request');
    }

    public function store(RepairIntakeRequest $request)
    {
        $user = $request->user(); // если нужна регистрация; если нет — сделай guest-flow позже

        return DB::transaction(function () use ($request, $user) {
            // 1. Создать Order с типом COMPLEX_REPAIR
            $order = Order::create([
                'user_id' => $user?->id,
                'service_type' => ServiceType::COMPLEX_REPAIR->value,
                'status' => 'pending_review', // сначала оценивает диспетчер
                'estimated_total' => null,  // смета появится позже
                'currency' => 'NOK',
                'payment_status' => 'pending',
                // TODO: city/zone/geo по адресу объекта
            ]);

            // 2. Создать RepairProject, привязанный к заказу
            $project = RepairProject::create([
                'order_id' => $order->id,
                'client_profile_id' => $user?->clientProfile?->id ?? null, // если есть ClientProfile
                'title' => $request->input('project_title') ?: 'Комплексный ремонт',
                'description' => $request->input('description'),
                'status' => 'assessment', // этап оценки
                'project_manager_id' => null, // назначит админ/координатор позже
                'address_line' => $request->input('address_line'),
                'postal_code' => $request->input('postal_code'),
                'city' => $request->input('city'),
                'planned_start_at' => $request->input('desired_start_at') ? \Carbon\Carbon::parse($request->input('desired_start_at')) : null,
                'planned_finish_at' => $request->input('desired_finish_at') ? \Carbon\Carbon::parse($request->input('desired_finish_at')) : null,
                'budget_estimate_minor' => null, // будет после сметы
                'budget_actual_minor' => null,
                'design_project_url' => $request->input('design_project_url'),
                'notes' => $request->input('notes'),
            ]);

            // 3. Сгенерировать базовые стадии проекта (демонтаж, черновые, чистовые)
            $this->createDefaultStagesForProject($project, $request->validated());

            event(new RepairProjectCreated($project));

            // 4. Отправить уведомление
            if ($user) {
                $this->feedService->push(
                    $user,
                    'repair.project_created',
                    'repair',
                    'Создана заявка на комплексный ремонт',
                    'Мы получили вашу заявку, свяжемся с вами для уточнения деталей.',
                    $order,
                    ['order_id' => $order->id, 'project_id' => $project->id]
                );
            }

            // TODO: уведомить координатора/менеджера (Notification/почта)

            // 5. Редирект в ЛК клиента на страницу заказа/проекта
            return redirect()
                ->route('account.orders.show', $order) // подстрой под реальный роут ЛК
                ->with('status', 'Заявка на комплексный ремонт отправлена. Мы свяжемся с вами для уточнения деталей.');
        });
    }

    public function createDefaultStagesForProject(RepairProject $project, array $data = []): void
    {
        $stages = [
            [
                'name' => 'Оценка и планирование',
                'description' => 'Выезд специалиста, замеры, уточнение пожеланий, финализация сметы.',
                'sequence' => 10,
            ],
            [
                'name' => 'Демонтаж и подготовка',
                'description' => 'Демонтаж старых покрытий, подготовка поверхностей, черновые работы.',
                'sequence' => 20,
            ],
            [
                'name' => 'Черновые работы',
                'description' => 'Коммуникации, выравнивание, подготовка под чистовую отделку.',
                'sequence' => 30,
            ],
            [
                'name' => 'Чистовая отделка',
                'description' => 'Плитка, декоративные покрытия, финальная отделка.',
                'sequence' => 40,
            ],
            [
                'name' => 'Приёмка и сдача объекта',
                'description' => 'Финальная проверка, устранение недочётов, подписание актов.',
                'sequence' => 50,
            ],
        ];

        foreach ($stages as $stage) {
            $project->stages()->create([
                'name' => $stage['name'],
                'description' => $stage['description'],
                'sequence' => $stage['sequence'],
                'status' => 'planned',
            ]);
        }
    }
}
