<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('partners')) {
            return;
        }

        Schema::table('partners', function (Blueprint $t) {
            if (! Schema::hasColumn('partners', 'type')) {
                $t->enum('type', ['logistics', 'pharmacy', 'grocery', 'service_provider', 'auto_service', 'recycling', 'other'])
                    ->default('service_provider')->index();
            }
            if (! Schema::hasColumn('partners', 'active')) {
                $t->boolean('active')->default(true)->index();
            }
            if (! Schema::hasColumn('partners', 'org_number')) {
                $t->string('org_number', 32)->nullable()->index();
            }
            if (! Schema::hasColumn('partners', 'vat_number')) {
                $t->string('vat_number', 32)->nullable();
            }
            if (! Schema::hasColumn('partners', 'vat_registered')) {
                $t->boolean('vat_registered')->default(true);
            }
            if (! Schema::hasColumn('partners', 'invoice_email')) {
                $t->string('invoice_email')->nullable();
            }
            if (! Schema::hasColumn('partners', 'support_email')) {
                $t->string('support_email')->nullable();
            }
            if (! Schema::hasColumn('partners', 'support_phone')) {
                $t->string('support_phone')->nullable();
            }
            if (! Schema::hasColumn('partners', 'sla_target_min')) {
                $t->unsignedSmallInteger('sla_target_min')->default(60);
            }
            if (! Schema::hasColumn('partners', 'on_time_rate')) {
                $t->decimal('on_time_rate', 5, 2)->default(100.00);
            }
            if (! Schema::hasColumn('partners', 'rating_avg')) {
                $t->decimal('rating_avg', 3, 2)->default(5.00);
            }
            if (! Schema::hasColumn('partners', 'rating_count')) {
                $t->unsignedInteger('rating_count')->default(0);
            }
            if (! Schema::hasColumn('partners', 'webhook_url')) {
                $t->string('webhook_url')->nullable();
            }
            if (! Schema::hasColumn('partners', 'api_key')) {
                $t->string('api_key', 64)->nullable();
            }
            if (! Schema::hasColumn('partners', 'payout_terms')) {
                $t->json('payout_terms')->nullable();
            }
            if (! Schema::hasColumn('partners', 'flags')) {
                $t->json('flags')->nullable();
            }
            if (! Schema::hasColumn('partners', 'contract_valid_to')) {
                $t->timestamp('contract_valid_to')->nullable()->index();
            }
            if (! Schema::hasColumn('partners', 'notes')) {
                $t->text('notes')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('partners')) {
            return;
        }

        Schema::table('partners', function (Blueprint $t) {
            foreach ([
                'type', 'active', 'org_number', 'vat_number', 'vat_registered', 'invoice_email', 'support_email',
                'support_phone', 'sla_target_min', 'on_time_rate', 'rating_avg', 'rating_count', 'webhook_url', 'api_key',
                'payout_terms', 'flags', 'contract_valid_to', 'notes',
            ] as $col) {
                if (Schema::hasColumn('partners', $col)) {
                    $t->dropColumn($col);
                }
            }
        });
    }
};
