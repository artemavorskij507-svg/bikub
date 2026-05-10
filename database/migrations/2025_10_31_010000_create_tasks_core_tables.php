<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('task_dependencies');
        Schema::dropIfExists('task_events');
        Schema::dropIfExists('tasks');

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('parent_task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->integer('sequence_index')->default(0);
            $table->string('type');
            $table->string('status')->default('queued');
            $table->string('priority')->default('normal');
            $table->foreignId('zone_id')->nullable()->constrained('geo_zones')->nullOnDelete();
            $table->foreignId('slot_id')->nullable()->constrained('schedule_slots')->nullOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('address_text')->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();
            $table->timestamp('window_start')->nullable();
            $table->timestamp('window_end')->nullable();
            $table->integer('expected_duration_min')->nullable();
            $table->json('requirements')->nullable();
            $table->decimal('price_component', 10, 2)->nullable();
            $table->decimal('payout_amount', 10, 2)->nullable();
            $table->string('currency', 3)->default('NOK');
            $table->timestamp('sla_deadline_at')->nullable();
            $table->boolean('proof_required')->default(false);
            $table->text('instructions')->nullable();
            $table->json('attachments')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['status', 'priority']);
            $table->index(['order_id', 'sequence_index']);
        });

        Schema::create('task_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('reason')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->index(['task_id', 'to_status']);
        });

        Schema::create('task_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
            $table->foreignId('depends_on_task_id')->constrained('tasks')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['task_id', 'depends_on_task_id']);
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('task_dependencies');
        Schema::dropIfExists('task_events');
        Schema::dropIfExists('tasks');

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users');
            $table->string('status')->default('queued');
            $table->integer('priority')->default(1);
            $table->json('checklist')->nullable();
            $table->json('proofs')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }
};
