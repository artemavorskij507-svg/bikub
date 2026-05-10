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
        Schema::table('handyman_services', function (Blueprint $table) {
            if (! Schema::hasColumn('handyman_services', 'slug')) {
                $table->string('slug')->unique()->nullable()->after('code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('handyman_services', function (Blueprint $table) {
            if (Schema::hasColumn('handyman_services', 'slug')) {
                $table->dropColumn('slug');
            }
        });
    }
};
