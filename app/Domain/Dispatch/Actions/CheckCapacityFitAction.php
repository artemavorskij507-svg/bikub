<?php

namespace App\Domain\Dispatch\Actions;

use App\Models\Operations\Executor;
use App\Models\Operations\ServiceJob;

class CheckCapacityFitAction
{
    public function execute(ServiceJob $job, Executor $executor): array
    {
        $requiredCapacity = is_array($job->required_capacity) ? $job->required_capacity : [];
        $requiredEquipment = is_array($job->required_equipment) ? $job->required_equipment : [];
        $requiredSkills = is_array($job->required_skills) ? $job->required_skills : [];

        $executorCapacity = is_array($executor->capacity) ? $executor->capacity : [];
        $executorEquipment = is_array($executor->equipment) ? $executor->equipment : [];
        $executorSkills = is_array($executor->skills) ? $executor->skills : [];
        $executorCapabilities = is_array($executor->capabilities) ? $executor->capabilities : [];

        $details = [];
        foreach ($requiredCapacity as $k => $v) {
            if (is_numeric($v) && ((float) ($executorCapacity[$k] ?? 0) < (float) $v)) {
                return [
                    'fits' => false,
                    'reason' => "capacity_mismatch:{$k}",
                    'details' => [
                        'required' => $requiredCapacity,
                        'executor' => $executorCapacity,
                    ],
                ];
            }
        }

        foreach ($requiredEquipment as $equipmentCode) {
            if (! in_array($equipmentCode, $executorEquipment, true)) {
                return [
                    'fits' => false,
                    'reason' => "missing_equipment:{$equipmentCode}",
                    'details' => [
                        'required_equipment' => $requiredEquipment,
                        'executor_equipment' => $executorEquipment,
                    ],
                ];
            }
        }

        foreach ($requiredSkills as $skillCode) {
            if (! in_array($skillCode, $executorSkills, true) && ! in_array($skillCode, $executorCapabilities, true)) {
                return [
                    'fits' => false,
                    'reason' => "missing_skill:{$skillCode}",
                    'details' => [
                        'required_skills' => $requiredSkills,
                        'executor_skills' => $executorSkills,
                        'executor_capabilities' => $executorCapabilities,
                    ],
                ];
            }
        }

        if ($job->service_domain === 'roadside' && ! empty($requiredEquipment)) {
            $details['roadside_capability_fit'] = true;
        }

        return [
            'fits' => true,
            'reason' => null,
            'details' => $details,
        ];
    }
}

