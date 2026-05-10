<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('work_specifications', function (Blueprint $table) {
            if (! Schema::hasColumn('work_specifications', 'public_id')) {
                $table->string('public_id')->unique()->nullable()->after('id');
            }
            if (! Schema::hasColumn('work_specifications', 'title')) {
                $table->string('title')->after('public_id');
            }
            if (! Schema::hasColumn('work_specifications', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
            if (! Schema::hasColumn('work_specifications', 'status')) {
                $table->string('status')->default('draft')->after('description');
            }
            if (! Schema::hasColumn('work_specifications', 'priority')) {
                $table->string('priority')->default('normal')->after('status');
            }
            if (! Schema::hasColumn('work_specifications', 'order_id')) {
                $table->foreignId('order_id')->nullable()->after('priority')->constrained('orders')->nullOnDelete();
            }
            if (! Schema::hasColumn('work_specifications', 'ticket_id')) {
                $table->uuid('ticket_id')->nullable()->after('order_id');
                // Проверяем существование таблицы support_tickets перед добавлением foreign key
                if (Schema::hasTable('support_tickets')) {
                    $table->foreign('ticket_id')->references('id')->on('support_tickets')->nullOnDelete();
                }
            }
            if (! Schema::hasColumn('work_specifications', 'responsible_id')) {
                $table->foreignId('responsible_id')->nullable()->after('ticket_id')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('work_specifications', 'creator_id')) {
                $table->foreignId('creator_id')->nullable()->after('responsible_id')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('work_specifications', 'worker_acknowledged_at')) {
                $table->timestamp('worker_acknowledged_at')->nullable()->after('creator_id');
            }
            if (! Schema::hasColumn('work_specifications', 'metadata')) {
                $table->json('metadata')->nullable()->after('worker_acknowledged_at');
            }
            if (! Schema::hasColumn('work_specifications', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        // Добавляем индексы отдельно, чтобы избежать ошибок если они уже существуют
        try {
            Schema::table('work_specifications', function (Blueprint $table) {
                $table->index('status');
            });
        } catch (\Exception $e) {
            // Индекс уже существует, пропускаем
        }

        try {
            Schema::table('work_specifications', function (Blueprint $table) {
                $table->index('priority');
            });
        } catch (\Exception $e) {
            // Индекс уже существует, пропускаем
        }

        try {
            Schema::table('work_specifications', function (Blueprint $table) {
                $table->index('worker_acknowledged_at');
            });
        } catch (\Exception $e) {
            // Индекс уже существует, пропускаем
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_specifications', function (Blueprint $table) {
            // Удаляем индексы
            try {
                $table->dropIndex(['status']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex(['priority']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex(['worker_acknowledged_at']);
            } catch (\Exception $e) {
            }

            // Удаляем foreign keys
            try {
                $table->dropForeign(['order_id']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropForeign(['ticket_id']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropForeign(['responsible_id']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropForeign(['creator_id']);
            } catch (\Exception $e) {
            }

            // Удаляем колонки
            $columnsToDrop = [];
            if (Schema::hasColumn('work_specifications', 'public_id')) {
                $columnsToDrop[] = 'public_id';
            }
            if (Schema::hasColumn('work_specifications', 'title')) {
                $columnsToDrop[] = 'title';
            }
            if (Schema::hasColumn('work_specifications', 'description')) {
                $columnsToDrop[] = 'description';
            }
            if (Schema::hasColumn('work_specifications', 'status')) {
                $columnsToDrop[] = 'status';
            }
            if (Schema::hasColumn('work_specifications', 'priority')) {
                $columnsToDrop[] = 'priority';
            }
            if (Schema::hasColumn('work_specifications', 'order_id')) {
                $columnsToDrop[] = 'order_id';
            }
            if (Schema::hasColumn('work_specifications', 'ticket_id')) {
                $columnsToDrop[] = 'ticket_id';
            }
            if (Schema::hasColumn('work_specifications', 'responsible_id')) {
                $columnsToDrop[] = 'responsible_id';
            }
            if (Schema::hasColumn('work_specifications', 'creator_id')) {
                $columnsToDrop[] = 'creator_id';
            }
            if (Schema::hasColumn('work_specifications', 'worker_acknowledged_at')) {
                $columnsToDrop[] = 'worker_acknowledged_at';
            }
            if (Schema::hasColumn('work_specifications', 'metadata')) {
                $columnsToDrop[] = 'metadata';
            }
            if (Schema::hasColumn('work_specifications', 'deleted_at')) {
                $columnsToDrop[] = 'deleted_at';
            }

            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
