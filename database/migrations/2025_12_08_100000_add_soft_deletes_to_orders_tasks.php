<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Добавляет soft deletes (deleted_at) колонку в таблицы Orders и Tasks
     * для сохранения аудит-логов и восстановления данных при необходимости.
     */
    public function up(): void
    {
        // Add deleted_at to orders table if it doesn't exist
        if (Schema::hasTable('orders') && ! Schema::hasColumn('orders', 'deleted_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->softDeletes()
                    ->comment('Soft delete timestamp for audit trail');
            });
        }

        // Add deleted_at to tasks table if it doesn't exist
        if (Schema::hasTable('tasks') && ! Schema::hasColumn('tasks', 'deleted_at')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->softDeletes()
                    ->comment('Soft delete timestamp for audit trail');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
