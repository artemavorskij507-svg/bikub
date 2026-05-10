<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('operations.{organizationId}.{domain}', function ($user, $organizationId) {
    return true;
});

Broadcast::channel('operations.{organizationId}.live', function ($user, $organizationId) {
    return true;
});

Broadcast::channel('operations.{organizationId}.sla', function ($user, $organizationId) {
    return true;
});

Broadcast::channel('operations.{organizationId}.exceptions', function ($user, $organizationId) {
    return true;
});

Broadcast::channel('ops.organization.{organizationId}', function ($user, $organizationId) {
    $userOrg = $user->organization_id ?? $user->default_org_id ?? null;

    return (string) $userOrg === (string) $organizationId
        || (method_exists($user, 'hasRole') && $user->hasRole('admin'));
});

Broadcast::channel('ops.job.{jobId}', function ($user, $jobId) {
    return (method_exists($user, 'can') && $user->can('ops.service_jobs.view'))
        || (method_exists($user, 'hasRole') && $user->hasRole('admin'));
});

Broadcast::channel('ops.executor.{executorId}', function ($user, $executorId) {
    return (method_exists($user, 'can') && $user->can('ops.executors.view'))
        || (method_exists($user, 'hasRole') && $user->hasRole('admin'));
});

Broadcast::channel('agent-os.organization.{organizationId}', function ($user, $organizationId) {
    $userOrg = $user->organization_id ?? $user->default_org_id ?? null;
    $isAdmin = false;
    if (method_exists($user, 'hasRole')) {
        try {
            $isAdmin = $user->hasRole('admin') || $user->hasRole('super_admin');
        } catch (\Throwable) {
            $isAdmin = false;
        }
    }

    return (string) $userOrg === (string) $organizationId
        || $isAdmin;
});

Broadcast::channel('agent-os.run.{runId}', function ($user, $runId) {
    $run = \App\Domain\AgentOS\Models\AgentRun::query()->select(['id', 'organization_id', 'tenant_id'])->find($runId);
    if (! $run) {
        return false;
    }

    if (method_exists($user, 'hasRole')) {
        try {
            if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
                return true;
            }
        } catch (\Throwable) {
            // continue with strict org/tenant check
        }
    }

    $userOrg = $user->organization_id ?? $user->default_org_id ?? null;
    $userTenant = $user->tenant_id ?? null;

    if ((string) $run->organization_id !== (string) $userOrg) {
        return false;
    }

    if ((string) ($run->tenant_id ?? '') !== (string) ($userTenant ?? '')) {
        return false;
    }

    return true;
});
