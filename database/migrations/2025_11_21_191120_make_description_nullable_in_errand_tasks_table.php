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
        // Skip if description column doesn't exist
        if (! Schema::hasColumn('errand_tasks', 'description')) {
            return;
        }

        Schema::table('errand_tasks', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('errand_tasks', function (Blueprint $table) {
            $table->text('description')->nullable(false)->change();
        });
    }
};
