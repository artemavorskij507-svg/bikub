<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('account_user_sessions')) {
            Schema::create('account_user_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('session_id')->unique();
                $table->string('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('last_activity')->nullable();
                $table->timestamps();
            });

            return;
        }

        Schema::table('account_user_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('account_user_sessions', 'user_id')) {
                $table->foreignId('user_id')->after('id')->constrained()->cascadeOnDelete();
            }

            if (! Schema::hasColumn('account_user_sessions', 'session_id')) {
                $table->string('session_id')->unique()->after('user_id');
            }

            if (! Schema::hasColumn('account_user_sessions', 'ip_address')) {
                $table->string('ip_address')->nullable()->after('session_id');
            }

            if (! Schema::hasColumn('account_user_sessions', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ip_address');
            }

            if (! Schema::hasColumn('account_user_sessions', 'last_activity')) {
                $table->timestamp('last_activity')->nullable()->after('user_agent');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_user_sessions');
    }
};
