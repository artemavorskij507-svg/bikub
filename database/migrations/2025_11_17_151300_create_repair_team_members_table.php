<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('executor_profile_id')->constrained('executor_profiles')->cascadeOnDelete();
            $table->string('role');
            $table->boolean('is_lead')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_team_members');
    }
};
