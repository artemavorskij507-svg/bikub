<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'eid_national_id')) {
                $table->string('eid_national_id')->nullable()->unique()->after('email');
            }

            if (! Schema::hasColumn('users', 'eid_provider')) {
                $table->string('eid_provider')->nullable()->after('eid_national_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'eid_provider')) {
                $table->dropColumn('eid_provider');
            }

            if (Schema::hasColumn('users', 'eid_national_id')) {
                $table->dropColumn('eid_national_id');
            }
        });
    }
};
