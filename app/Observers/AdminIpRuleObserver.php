<?php

namespace App\Observers;

use App\Models\AdminIpRule;
use App\Services\AuditLogger;

class AdminIpRuleObserver
{
    protected function audit(string $action, ?AdminIpRule $model, $before = null, $after = null)
    {
        try {
            app(AuditLogger::class)->log(
                $action,
                AdminIpRule::class,
                $model?->id,
                $before,
                $after,
                request()
            );
        } catch (\Throwable $e) {
            // Fail silently — do not block primary action for logging issues
            \Illuminate\Support\Facades\Log::warning('Audit logging failed for AdminIpRuleObserver', ['error' => $e->getMessage()]);
        }
    }

    public function creating(AdminIpRule $model)
    {
        // nothing yet — creation confirmation handled in resource
    }

    public function created(AdminIpRule $model)
    {
        $this->audit('admin_ip_rule_created', $model, null, $model->toArray());
    }

    public function updating(AdminIpRule $model)
    {
        $dirty = $model->getDirty();

        // If deactivating an allow rule, ensure it's not the last active allow
        if (array_key_exists('is_active', $dirty) || array_key_exists('type', $dirty)) {
            $newIsActive = $model->getAttribute('is_active');
            $newType = $model->getAttribute('type');

            // If the new state will be inactive AND type is 'allow'
            if (! $newIsActive && $newType === 'allow') {
                $count = AdminIpRule::where('type', 'allow')->where('is_active', true)->where('id', '!=', $model->id)->count();
                if ($count === 0) {
                    // Log the blocked attempt with relevant minimal payload
                    $before = array_intersect_key($model->getOriginal(), $dirty);
                    $after = array_merge($dirty, ['outcome' => 'denied', 'attempted_action' => 'deactivate']);
                    $this->audit('admin_ip_rule_blocked_last_allow_attempt', $model, $before, $after);

                    throw new \Exception('Cannot deactivate the last active allow rule. At least one active allow rule must exist to keep admin access.');
                }
            }
        }
    }

    public function updated(AdminIpRule $model)
    {
        $changes = $model->getChanges();

        // Remove timestamp-only changes
        $changes = array_diff_key($changes, array_flip(['updated_at']));

        if (empty($changes)) {
            return;
        }

        // If the only change is is_active, log activation/deactivation only
        if (count($changes) === 1 && array_key_exists('is_active', $changes)) {
            $this->audit($model->is_active ? 'admin_ip_rule_activated' : 'admin_ip_rule_deactivated', $model, null, ['is_active' => $model->is_active]);

            return;
        }

        // For broader updates: log only changed fields (before and after)
        $before = array_intersect_key($model->getOriginal(), $changes);
        $after = $changes;
        $this->audit('admin_ip_rule_updated', $model, $before, $after);

        // If is_active was among other changes, also log activation/deactivation as a separate event
        if (array_key_exists('is_active', $changes)) {
            $this->audit($model->is_active ? 'admin_ip_rule_activated' : 'admin_ip_rule_deactivated', $model, null, ['is_active' => $model->is_active]);
        }
    }

    public function deleting(AdminIpRule $model)
    {
        // Prevent deleting last active allow rule
        if ($model->type === 'allow' && $model->is_active) {
            $count = AdminIpRule::where('type', 'allow')->where('is_active', true)->where('id', '!=', $model->id)->count();
            if ($count === 0) {
                // Log blocked delete attempt
                $before = $model->toArray();
                $after = ['outcome' => 'denied', 'attempted_action' => 'delete'];
                $this->audit('admin_ip_rule_blocked_last_allow_attempt', $model, $before, $after);

                throw new \Exception('Cannot delete the last active allow rule. At least one active allow rule must exist to keep admin access.');
            }
        }
    }

    public function deleted(AdminIpRule $model)
    {
        $this->audit('admin_ip_rule_deleted', $model, $model->toArray(), null);
    }
}
