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
        Schema::create('errand_order_details', function (Blueprint $table) {
            $table->id();

            // Связь с заказом
            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            // Краткая категоризация поручения:
            // Например: documents, courier, shopping, queue, visit, custom
            $table->string('category', 64)->nullable();

            // Полное текстовое описание задачи от клиента
            $table->text('description');

            // Маршрут
            // Человекочитаемые адреса
            $table->string('from_address')->nullable();
            $table->string('to_address')->nullable();

            // Можно сразу заложить хранение нормализованных координат, если в проекте так принято
            $table->decimal('from_lat', 10, 7)->nullable();
            $table->decimal('from_lng', 10, 7)->nullable();
            $table->decimal('to_lat', 10, 7)->nullable();
            $table->decimal('to_lng', 10, 7)->nullable();

            // Промежуточные точки маршрута (массив объектов)
            $table->jsonb('waypoints')->nullable();
            // Структура, как минимум:
            // [{ "label": "Остановиться у банка", "address": "...", "lat": ..., "lng": ... }, ...]

            // Контактные лица
            $table->jsonb('contacts')->nullable();
            // Возможная структура:
            // { "from": { "name": "...", "phone": "..." }, "to": { ... }, "extra": [...] }

            // Тайминг
            $table->timestamp('desired_start_at')->nullable();
            $table->timestamp('desired_finish_at')->nullable();

            // Флаги опций
            $table->boolean('is_urgent')->default(false);
            $table->boolean('requires_signature')->default(false);
            $table->boolean('requires_trusted_helper')->default(false);
            $table->boolean('involves_documents')->default(false);

            // Оценка сложности диспетчером (0–5 или 0–10)
            $table->unsignedTinyInteger('complexity_level')->nullable(); // 1..5, где 1 — очень просто

            // Ожидаемая длительность (минуты), оценка диспетчером
            $table->unsignedInteger('expected_duration_minutes')->nullable();

            // Аванс/лимит на покупки (например, продукты/товары)
            $table->unsignedInteger('material_advance_amount')->nullable();
            // Храним в "minor units" (øre/cent) по общей политике проекта

            // Базовые поля под ценообразование (формула будет реализована позже)
            $table->unsignedInteger('base_fee')->nullable();
            $table->unsignedInteger('distance_fee')->nullable();
            $table->unsignedInteger('time_fee')->nullable();
            $table->unsignedInteger('complexity_fee')->nullable();
            $table->unsignedInteger('trusted_helper_fee')->nullable();
            $table->unsignedInteger('urgency_fee')->nullable();
            $table->unsignedInteger('total_estimated_price')->nullable();

            // Связи на будущее: диспетчер и исполняющий исполнитель
            $table->foreignId('dispatcher_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('executor_profile_id')
                ->nullable()
                ->constrained('executor_profiles')
                ->nullOnDelete();

            // Тех. поле для хранения любых доп. параметров (риск-оценка, комментарии диспетчера и т.д.)
            $table->jsonb('meta')->nullable();

            $table->timestamps();

            $table->index('category');
            $table->index('is_urgent');
            $table->index('requires_trusted_helper');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('errand_order_details');
    }
};
