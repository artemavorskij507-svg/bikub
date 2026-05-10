<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // В PostgreSQL для изменения enum нужно создать новый тип и изменить колонку
        // Альтернатива: изменить enum на string для упрощения
        \DB::statement('ALTER TABLE support_tickets DROP CONSTRAINT IF EXISTS support_tickets_status_check');
        \DB::statement('ALTER TABLE support_tickets ALTER COLUMN status TYPE VARCHAR(255)');
        \DB::statement("ALTER TABLE support_tickets ADD CONSTRAINT support_tickets_status_check CHECK (status IN ('new', 'open', 'pending', 'in_progress', 'resolved', 'closed'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Возвращаем обратно к enum (если нужно)
        \DB::statement('ALTER TABLE support_tickets DROP CONSTRAINT IF EXISTS support_tickets_status_check');
        \DB::statement('ALTER TABLE support_tickets ALTER COLUMN status TYPE VARCHAR(255)');
        \DB::statement("ALTER TABLE support_tickets ADD CONSTRAINT support_tickets_status_check CHECK (status IN ('new', 'open', 'pending', 'resolved', 'closed'))");
    }
};
