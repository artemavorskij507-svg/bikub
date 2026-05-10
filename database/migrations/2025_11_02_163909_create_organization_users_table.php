<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('organization_users')) {
            Schema::create('organization_users', function (Blueprint $table) {
                $table->id();
                $table->uuid('organization_id')->constrained('organizations')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('role')->default('member'); // admin, operator, member
                $table->json('permissions')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('joined_at')->useCurrent();
                $table->timestamps();

                $table->unique(['organization_id', 'user_id']);
                $table->index(['organization_id', 'is_active']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_users');
    }
};
