<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('workbench_idempotency_keys')) {
            return;
        }

        Schema::create('workbench_idempotency_keys', function (Blueprint $table): void {
            $table->id();

            $table->string('organization_id', 64)->index();
            $table->unsignedBigInteger('user_id')->index();

            $table->string('action_name', 100)->index();
            $table->string('idempotency_key', 191);

            $table->string('target_type', 100)->nullable()->index();
            $table->unsignedBigInteger('target_id')->nullable()->index();

            $table->string('request_hash', 64);
            $table->string('state', 20)->default('processing')->index();

            $table->unsignedSmallInteger('response_status')->nullable();
            $table->json('response_body_json')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();

            $table->timestamps();

            $table->unique(
                ['organization_id', 'user_id', 'action_name', 'idempotency_key'],
                'workbench_idempotency_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workbench_idempotency_keys');
    }
};
