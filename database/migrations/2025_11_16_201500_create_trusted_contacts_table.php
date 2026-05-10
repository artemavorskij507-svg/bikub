<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trusted_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_profile_id')->constrained('client_profiles')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('full_name');
            $table->string('relationship'); // дочь, сын, опекун, соцработник
            $table->string('phone');
            $table->string('email');
            $table->boolean('can_manage_orders')->default(true);
            $table->boolean('can_view_reports')->default(true);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index('client_profile_id');
            $table->index('is_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trusted_contacts');
    }
};
