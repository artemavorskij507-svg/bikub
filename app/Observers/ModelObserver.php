<?php

namespace App\Observers;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;

class ModelObserver
{
    protected AuditLogger $logger;

    public function __construct()
    {
        $this->logger = app(AuditLogger::class);
    }

    public function created(Model $model): void
    {
        $this->logger->log('create', get_class($model), $model->getKey(), null, $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();
        $original = $model->getOriginal();
        $this->logger->log('update', get_class($model), $model->getKey(), $original, $changes);
    }

    public function deleted(Model $model): void
    {
        $this->logger->log('delete', get_class($model), $model->getKey(), $model->getAttributes(), null);
    }
}
