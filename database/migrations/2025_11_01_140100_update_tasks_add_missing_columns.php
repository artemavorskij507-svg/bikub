<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('tasks', 'type')) {
                $table->string('type')->default('pickup');
            }
            if (! Schema::hasColumn('tasks', 'status')) {
                $table->string('status')->default('queued');
            }
            if (! Schema::hasColumn('tasks', 'priority')) {
                $table->string('priority')->default('normal');
            }
            if (! Schema::hasColumn('tasks', 'assignee_id')) {
                $table->foreignId('assignee_id')->nullable()->constrained('employees')->nullOnDelete();
            }
            if (! Schema::hasColumn('tasks', 'zone_id')) {
                $table->foreignId('zone_id')->nullable()->constrained('geo_zones')->nullOnDelete();
            }
            if (! Schema::hasColumn('tasks', 'slot_id')) {
                $table->foreignId('slot_id')->nullable()->constrained('schedule_slots')->nullOnDelete();
            }
            if (! Schema::hasColumn('tasks', 'address_text')) {
                $table->string('address_text')->nullable();
            }
            if (! Schema::hasColumn('tasks', 'lat')) {
                $table->decimal('lat', 10, 8)->nullable();
            }
            if (! Schema::hasColumn('tasks', 'lng')) {
                $table->decimal('lng', 11, 8)->nullable();
            }
            if (! Schema::hasColumn('tasks', 'expected_duration_min')) {
                $table->integer('expected_duration_min')->nullable();
            }
            if (! Schema::hasColumn('tasks', 'window_start')) {
                $table->timestamp('window_start')->nullable();
            }
            if (! Schema::hasColumn('tasks', 'window_end')) {
                $table->timestamp('window_end')->nullable();
            }
            if (! Schema::hasColumn('tasks', 'proof_required')) {
                $table->boolean('proof_required')->default(false);
            }
            if (! Schema::hasColumn('tasks', 'instructions')) {
                $table->text('instructions')->nullable();
            }
            if (! Schema::hasColumn('tasks', 'requirements')) {
                $table->json('requirements')->nullable();
            }
            if (! Schema::hasColumn('tasks', 'attachments')) {
                $table->json('attachments')->nullable();
            }
            if (! Schema::hasColumn('tasks', 'meta')) {
                $table->json('meta')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $cols = [
                'type', 'status', 'priority', 'assignee_id', 'zone_id', 'slot_id', 'address_text', 'lat', 'lng', 'expected_duration_min', 'window_start', 'window_end', 'proof_required', 'instructions', 'requirements', 'attachments', 'meta',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('tasks', $col)) {
                    if (in_array($col, ['assignee_id', 'zone_id', 'slot_id'])) {
                        $table->dropConstrainedForeignId($col);
                    } else {
                        $table->dropColumn($col);
                    }
                }
            }
        });
    }
};
