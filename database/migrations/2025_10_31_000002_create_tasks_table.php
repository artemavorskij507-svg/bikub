<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tasks')) {
            Schema::create('tasks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_item_id')->constrained()->onDelete('cascade');
                $table->foreignId('assigned_employee_id')->nullable()->constrained('employees')->nullOnDelete();
                $table->foreignId('assigned_partner_id')->nullable()->constrained('partners')->nullOnDelete();
                $table->string('type'); // picking|delivery|inventory
                $table->string('status')->default('queued'); // queued|assigned|in_progress|done|failed
                $table->string('priority')->default('normal');
                $table->timestamp('planned_start_at')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->json('current_location')->nullable(); // {lat,lng}
                $table->integer('eta_minutes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'eta_minutes')) {
                $table->integer('eta_minutes')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('tasks')) {
            Schema::dropIfExists('tasks');
        }
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'eta_minutes')) {
                $table->dropColumn('eta_minutes');
            }
        });
    }
};
