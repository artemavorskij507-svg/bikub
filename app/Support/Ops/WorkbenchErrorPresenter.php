<?php

namespace App\Support\Ops;

use App\Domain\Dispatch\Exceptions\DispatchConflictException;
use App\Domain\Dispatch\Exceptions\ExecutorUnavailableException;
use App\Domain\Dispatch\Exceptions\StaleDrawerVersionException;
use Throwable;

class WorkbenchErrorPresenter
{
    public static function message(Throwable $e): string
    {
        return match (true) {
            $e instanceof DispatchConflictException => 'This job is being updated by another dispatcher.',
            $e instanceof ExecutorUnavailableException => 'Executor is no longer available for assignment.',
            $e instanceof StaleDrawerVersionException => 'This drawer is outdated. Data has been refreshed.',
            default => 'Workbench action failed. Please retry.',
        };
    }

    public static function status(Throwable $e): int
    {
        return match (true) {
            $e instanceof DispatchConflictException => 409,
            $e instanceof ExecutorUnavailableException => 422,
            $e instanceof StaleDrawerVersionException => 409,
            default => 500,
        };
    }

    public static function code(Throwable $e): string
    {
        return match (true) {
            $e instanceof DispatchConflictException => 'dispatch_conflict',
            $e instanceof ExecutorUnavailableException => 'executor_unavailable',
            $e instanceof StaleDrawerVersionException => 'stale_drawer_version',
            default => 'workbench_action_failed',
        };
    }
}

