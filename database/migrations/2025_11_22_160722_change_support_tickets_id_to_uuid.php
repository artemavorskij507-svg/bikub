<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Список таблиц, которые могут ссылаться на support_tickets.id
        $dependentTables = [
            'ticket_messages',
            'ticket_tag_map',
            'ticket_escalations',
            'ticket_satisfaction',
            'kb_ticket_links',
            'support_ticket_messages',
        ];

        // Удаляем все внешние ключи, которые ссылаются на support_tickets.id
        foreach ($dependentTables as $table) {
            if (Schema::hasTable($table)) {
                // Получаем список внешних ключей для таблицы
                $foreignKeys = DB::select("
                    SELECT constraint_name 
                    FROM information_schema.table_constraints 
                    WHERE table_name = ? 
                    AND constraint_type = 'FOREIGN KEY'
                    AND constraint_name LIKE '%ticket_id%'
                ", [$table]);

                foreach ($foreignKeys as $fk) {
                    try {
                        DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$fk->constraint_name} CASCADE");
                    } catch (\Exception $e) {
                        // Игнорируем ошибки, если ограничение уже удалено
                    }
                }
            }
        }

        // Удаляем первичный ключ с CASCADE
        DB::statement('ALTER TABLE support_tickets DROP CONSTRAINT IF EXISTS support_tickets_pkey CASCADE');

        // Удаляем старую колонку id
        DB::statement('ALTER TABLE support_tickets DROP COLUMN IF EXISTS id CASCADE');

        // Добавляем новую колонку id типа uuid
        DB::statement('ALTER TABLE support_tickets ADD COLUMN id UUID PRIMARY KEY DEFAULT gen_random_uuid()');

        // Обновляем колонки ticket_id в зависимых таблицах
        foreach ($dependentTables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'ticket_id')) {
                // Удаляем старую колонку ticket_id
                DB::statement("ALTER TABLE {$table} DROP COLUMN IF EXISTS ticket_id CASCADE");

                // Добавляем новую колонку ticket_id типа uuid
                DB::statement("ALTER TABLE {$table} ADD COLUMN ticket_id UUID");

                // Восстанавливаем внешний ключ
                try {
                    DB::statement("
                        ALTER TABLE {$table} 
                        ADD CONSTRAINT {$table}_ticket_id_foreign 
                        FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE
                    ");
                } catch (\Exception $e) {
                    // Игнорируем, если ограничение уже существует
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Список таблиц, которые могут ссылаться на support_tickets.id
        $dependentTables = [
            'ticket_messages',
            'ticket_tag_map',
            'ticket_escalations',
            'ticket_satisfaction',
            'kb_ticket_links',
            'support_ticket_messages',
        ];

        // Удаляем все внешние ключи
        foreach ($dependentTables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'ticket_id')) {
                try {
                    DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$table}_ticket_id_foreign CASCADE");
                    DB::statement("ALTER TABLE {$table} DROP COLUMN IF EXISTS ticket_id CASCADE");
                    DB::statement("ALTER TABLE {$table} ADD COLUMN ticket_id BIGINT");
                } catch (\Exception $e) {
                    // Игнорируем ошибки
                }
            }
        }

        // Удаляем первичный ключ
        DB::statement('ALTER TABLE support_tickets DROP CONSTRAINT IF EXISTS support_tickets_pkey CASCADE');

        // Удаляем колонку id
        DB::statement('ALTER TABLE support_tickets DROP COLUMN IF EXISTS id CASCADE');

        // Добавляем колонку id типа bigint с автоинкрементом
        DB::statement('ALTER TABLE support_tickets ADD COLUMN id BIGSERIAL PRIMARY KEY');
    }
};
