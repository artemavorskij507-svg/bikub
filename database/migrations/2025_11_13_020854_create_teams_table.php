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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('leader_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('active')->comment('active, inactive, suspended');
            $table->integer('max_orders')->default(5)->comment('Maximum concurrent orders');
            $table->decimal('rating', 3, 2)->default(0)->comment('Average team rating 0-5');
            $table->integer('completed_orders_count')->default(0);
            $table->json('specializations')->nullable()->comment('types of moves this team handles');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('leader_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
