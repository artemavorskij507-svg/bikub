<?php

namespace App\Services\Handyman;

use App\Events\HandymanAssignmentStatusChanged;
use App\Models\ExecutorProfile;
use App\Models\HandymanAssignment;
use App\Models\HandymanOrderDetails;
use App\Models\HandymanService;
use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HandymanAssignmentService
{
    public function __construct(
        protected HandymanMatchingService $matchingService
    ) {}

    /**
     * Предложить назначения для заказа.
     *
     * @return Collection<HandymanAssignment>
     */
    public function proposeAssignmentsForOrder(Order $order): Collection
    {
        $details = $order->handymanDetails;
        if (! $details) {
            return collect();
        }

        $service = $details->handymanService;

        $candidates = $this->matchingService->findCandidates($order, $details, $service);

        return DB::transaction(function () use ($order, $details, $service, $candidates) {
            $assignments = collect();

            foreach ($candidates as $index => $executor) {
                /** @var ExecutorProfile $executor */
                $assignment = HandymanAssignment::create([
                    'order_id' => $order->id,
                    'executor_profile_id' => $executor->id,
                    'repair_project_id' => null,
                    'status' => 'proposed',
                    'planned_start_at' => null, // будет назначено после выбора слота
                    'planned_finish_at' => null,
                    'score' => $this->calculateScore($order, $details, $service, $executor),
                    'is_primary' => $index === 0,
                    'meta' => [
                        'matching_version' => 1,
                    ],
                ]);

                event(new HandymanAssignmentStatusChanged($assignment, 'new', $assignment->status));

                $assignments->push($assignment);
            }

            return $assignments;
        });
    }

    /**
     * Принять назначение.
     */
    public function acceptAssignment(HandymanAssignment $assignment): void
    {
        $oldStatus = $assignment->status;

        $assignment->update([
            'status' => 'accepted',
        ]);

        $assignment->refresh();

        event(new HandymanAssignmentStatusChanged($assignment, $oldStatus, $assignment->status));

        // Все прочие назначения для этого заказа помечаем как 'reassigned' или 'cancelled'
        HandymanAssignment::where('order_id', $assignment->order_id)
            ->where('id', '<>', $assignment->id)
            ->whereIn('status', ['proposed', 'accepted'])
            ->update([
                'status' => 'reassigned',
            ]);
    }

    /**
     * Рассчитать score для назначения.
     */
    protected function calculateScore(
        Order $order,
        HandymanOrderDetails $details,
        ?HandymanService $service,
        ExecutorProfile $executor
    ): int {
        // Простейший пример: рейтинг мастера + 10 * кол-во совпавших skills
        $score = (int) ($executor->rating ?? 0);

        if ($service && ! empty($service->required_skills) && is_array($executor->skills ?? [])) {
            $matches = array_intersect($service->required_skills, $executor->skills);
            $score += count($matches) * 10;
        }

        return $score;
    }
}
