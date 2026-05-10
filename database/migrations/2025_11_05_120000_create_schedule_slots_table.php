<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        if (Schema::hasColumn('orders', 'schedule_slot_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropForeign(['schedule_slot_id']);
                $table->dropColumn('schedule_slot_id');
            });
        }

        if (Schema::hasColumn('tasks', 'slot_id')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropForeign(['slot_id']);
            });
        }

        Schema::dropIfExists('order_schedule_slot');
        Schema::dropIfExists('schedule_slot_employees');
        Schema::dropIfExists('schedule_slots');

        Schema::create('schedule_slots', function (Blueprint $table) {
            $table->id();
            $table->uuid('org_id')->nullable()->index();
            $table->foreignId('zone_id')->nullable()->constrained('geo_zones')->nullOnDelete();
            $table->foreignId('service_type_id')->nullable()->constrained('service_types')->nullOnDelete();
            $table->enum('kind', ['delivery', 'pickup', 'service', 'shuttle'])->default('delivery')->index();
            $table->timestampTz('start_at')->index();
            $table->timestampTz('end_at')->index();
            $table->boolean('hard_window')->default(false);
            $table->unsignedSmallInteger('buffer_before_min')->default(0);
            $table->unsignedSmallInteger('buffer_after_min')->default(0);
            $table->unsignedSmallInteger('capacity_total')->default(10);
            $table->unsignedSmallInteger('capacity_reserved')->default(0);
            $table->unsignedSmallInteger('capacity_confirmed')->default(0);
            $table->unsignedSmallInteger('max_orders')->nullable();
            $table->unsignedSmallInteger('courier_required')->default(1);
            $table->unsignedSmallInteger('courier_assigned')->default(0);
            $table->decimal('max_distance_km', 6, 2)->nullable();
            $table->json('features')->nullable();
            $table->json('meta')->nullable();
            $table->enum('status', ['open', 'hold', 'locked', 'closed'])->default('open')->index();
            $table->timestampTz('lock_expires_at')->nullable();
            $table->string('code')->nullable()->unique();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['zone_id', 'kind', 'start_at']);
        });

        Schema::create('schedule_slot_employees', function (Blueprint $table) {
            $table->foreignId('slot_id')->constrained('schedule_slots')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->json('skills')->nullable();
            $table->boolean('lead')->default(false);
            $table->primary(['slot_id', 'employee_id']);
        });

        Schema::create('order_schedule_slot', function (Blueprint $table) {
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('slot_id')->constrained('schedule_slots')->cascadeOnDelete();
            $table->enum('reservation_status', ['hold', 'confirmed'])->default('hold')->index();
            $table->timestampTz('expires_at')->nullable();
            $table->primary(['order_id', 'slot_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('schedule_slot_id')->nullable()->after('scheduled_at')->constrained('schedule_slots')->nullOnDelete();
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreign('slot_id')->references('id')->on('schedule_slots')->nullOnDelete();
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        if (Schema::hasColumn('orders', 'schedule_slot_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropForeign(['schedule_slot_id']);
                $table->dropColumn('schedule_slot_id');
            });
        }

        if (Schema::hasColumn('tasks', 'slot_id')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropForeign(['slot_id']);
            });
        }

        Schema::dropIfExists('order_schedule_slot');
        Schema::dropIfExists('schedule_slot_employees');
        Schema::dropIfExists('schedule_slots');

        Schema::create('schedule_slots', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->time('from');
            $table->time('to');
            $table->json('dow')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('max_orders')->nullable();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('schedule_slot_id')->nullable()->constrained('schedule_slots')->nullOnDelete();
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreign('slot_id')->references('id')->on('schedule_slots')->nullOnDelete();
        });

        Schema::enableForeignKeyConstraints();
    }
};
