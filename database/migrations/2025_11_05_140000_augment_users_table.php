<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            if (! Schema::hasColumn('users', 'phone_e164')) {
                $t->string('phone_e164')->nullable()->unique();
            }

            if (! Schema::hasColumn('users', 'email_verified_at')) {
                $t->timestamp('email_verified_at')->nullable();
            }

            if (! Schema::hasColumn('users', 'phone_verified_at')) {
                $t->timestamp('phone_verified_at')->nullable();
            }

            if (! Schema::hasColumn('users', 'last_login_at')) {
                $t->timestamp('last_login_at')->nullable()->index();
            }

            if (! Schema::hasColumn('users', 'last_order_at')) {
                $t->timestamp('last_order_at')->nullable()->index();
            }

            if (! Schema::hasColumn('users', 'orders_count')) {
                $t->unsignedInteger('orders_count')->default(0)->index();
            }

            if (! Schema::hasColumn('users', 'ltv_cents')) {
                $t->bigInteger('ltv_cents')->default(0)->index();
            }

            if (! Schema::hasColumn('users', 'aov_cents')) {
                $t->bigInteger('aov_cents')->default(0);
            }

            if (! Schema::hasColumn('users', 'risk_level')) {
                $t->enum('risk_level', ['low', 'medium', 'high'])->default('low')->index();
            }

            if (! Schema::hasColumn('users', 'two_factor_enabled')) {
                $t->boolean('two_factor_enabled')->default(false)->index();
            }

            if (! Schema::hasColumn('users', 'suspended_at')) {
                $t->timestamp('suspended_at')->nullable()->index();
            }

            if (! Schema::hasColumn('users', 'locale')) {
                $t->string('locale', 12)->default('ru')->index();
            }

            if (! Schema::hasColumn('users', 'timezone')) {
                $t->string('timezone', 64)->nullable();
            }

            if (! Schema::hasColumn('users', 'marketing_opt_in')) {
                $t->boolean('marketing_opt_in')->default(false);
            }

            if (! Schema::hasColumn('users', 'consents')) {
                $t->json('consents')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $columns = [
                'phone_e164',
                'email_verified_at',
                'phone_verified_at',
                'last_login_at',
                'last_order_at',
                'orders_count',
                'ltv_cents',
                'aov_cents',
                'risk_level',
                'two_factor_enabled',
                'suspended_at',
                'locale',
                'timezone',
                'marketing_opt_in',
                'consents',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $t->dropColumn($column);
                }
            }
        });
    }
};
