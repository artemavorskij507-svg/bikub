<?php

namespace App\Services\Claims;

use App\Models\Claim;

class ClaimSlaService
{
    public function setInitialSla(Claim $claim): Claim
    {
        $created = $claim->created_at ?? now();
        $severity = $claim->severity ?? 'medium';

        // базовые правила SLA (можно потом перенести в конфиг/таблицу)
        switch ($severity) {
            case 'critical':
                $responseMinutes = 15;
                $resolutionHours = 4;
                break;

            case 'high':
                $responseMinutes = 60;
                $resolutionHours = 24;
                break;

            case 'medium':
                $responseMinutes = 4 * 60;
                $resolutionHours = 48;
                break;

            case 'low':
            default:
                $responseMinutes = 24 * 60;
                $resolutionHours = 72;
                break;
        }

        $claim->sla_response_due_at = $created->copy()->addMinutes($responseMinutes);
        $claim->sla_resolution_due_at = $created->copy()->addHours($resolutionHours);

        $claim->save();

        return $claim;
    }

    public function updateSlaBreaches(Claim $claim): Claim
    {
        $now = now();

        if ($claim->sla_response_due_at && ! $claim->responded_at && ! $claim->sla_response_breached) {
            if ($now->gt($claim->sla_response_due_at)) {
                $claim->sla_response_breached = true;
            }
        }

        if ($claim->sla_resolution_due_at && ! $claim->resolved_at && ! $claim->sla_resolution_breached) {
            if ($now->gt($claim->sla_resolution_due_at)) {
                $claim->sla_resolution_breached = true;
            }
        }

        $claim->save();

        return $claim;
    }

    public function markResponded(Claim $claim): Claim
    {
        if (! $claim->responded_at) {
            $claim->responded_at = now();
            $claim->save();
        }

        return $claim;
    }

    public function markResolved(Claim $claim): Claim
    {
        if (! $claim->resolved_at) {
            $claim->resolved_at = now();
            $claim->save();
        }

        return $claim;
    }
}
