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
        // VAT Rates
        if (! Schema::hasTable('vat_rates')) {
            Schema::create('vat_rates', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('country', 2); // ISO country code
                $table->string('category'); // service category
                $table->decimal('rate', 5, 2); // VAT rate percentage
                $table->date('valid_from');
                $table->date('valid_to')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['country', 'category', 'valid_from']);
            });
        }

        // Payout Schedules
        if (! Schema::hasTable('payout_schedules')) {
            Schema::create('payout_schedules', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();
                $table->enum('frequency', ['weekly', 'biweekly', 'monthly']);
                $table->timestamp('next_run');
                $table->timestamp('last_run')->nullable();
                $table->enum('status', ['active', 'paused', 'cancelled']);
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }

        // Loyalty Wallets
        if (! Schema::hasTable('loyalty_wallets')) {
            Schema::create('loyalty_wallets', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->unsignedBigInteger('user_id')->unique();
                $table->integer('balance')->default(0); // Points balance
                $table->integer('total_earned')->default(0);
                $table->integer('total_spent')->default(0);
                $table->enum('tier', ['bronze', 'silver', 'gold', 'platinum'])->default('bronze');
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // Loyalty Ledger
        if (! Schema::hasTable('loyalty_ledger')) {
            Schema::create('loyalty_ledger', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('wallet_id');
                $table->integer('delta'); // Positive for earned, negative for spent
                $table->enum('type', ['earn', 'spend', 'expire', 'adjustment']);
                $table->string('reason');
                $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->uuid('referral_id')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->foreign('wallet_id')->references('id')->on('loyalty_wallets')->onDelete('cascade');
            });
        }

        // Gift Cards
        if (! Schema::hasTable('gift_cards')) {
            Schema::create('gift_cards', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('code')->unique();
                $table->decimal('amount', 12, 2);
                $table->decimal('balance', 12, 2);
                $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('used_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('expires_at')->nullable();
                $table->enum('status', ['active', 'used', 'expired', 'cancelled']);
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }

        // Referrals
        if (! Schema::hasTable('referrals')) {
            Schema::create('referrals', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->unsignedBigInteger('inviter_id');
                $table->unsignedBigInteger('invitee_id');
                $table->string('referral_code');
                $table->integer('inviter_bonus')->default(0);
                $table->integer('invitee_bonus')->default(0);
                $table->enum('status', ['pending', 'completed', 'cancelled']);
                $table->timestamp('completed_at')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->foreign('inviter_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('invitee_id')->references('id')->on('users')->onDelete('cascade');

                $table->unique(['inviter_id', 'invitee_id']);
                $table->index('referral_code');
            });
        }

        // Risk Events
        if (! Schema::hasTable('risk_events')) {
            Schema::create('risk_events', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('device_id')->nullable();
                $table->string('ip_address')->nullable();
                $table->enum('type', [
                    'multiple_payment_attempts', 'suspicious_order_pattern',
                    'device_fingerprint_mismatch', 'velocity_limit_exceeded',
                    'blacklisted_card', 'fraudulent_activity',
                ]);
                $table->integer('risk_score'); // 0-100
                $table->enum('severity', ['low', 'medium', 'high', 'critical']);
                $table->json('metadata')->nullable();
                $table->enum('status', ['open', 'investigating', 'resolved', 'false_positive']);
                $table->foreignId('investigated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('investigated_at')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

                $table->index(['type', 'risk_score']);
                $table->index(['device_id', 'created_at']);
            });
        }

        // Device Fingerprints
        if (! Schema::hasTable('device_fingerprints')) {
            Schema::create('device_fingerprints', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('device_id')->unique();
                $table->string('fingerprint_hash');
                $table->json('device_info'); // Browser, OS, screen resolution, etc.
                $table->string('ip_address');
                $table->string('user_agent');
                $table->boolean('is_trusted')->default(false);
                $table->integer('risk_score')->default(0);
                $table->timestamp('last_seen_at');
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
                $table->index('fingerprint_hash');
            });
        }

        // Blacklists
        if (! Schema::hasTable('blacklists')) {
            Schema::create('blacklists', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->enum('type', ['email', 'phone', 'card', 'device', 'ip']);
                $table->string('value');
                $table->enum('reason', ['fraud', 'chargeback', 'policy_violation', 'manual']);
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['type', 'value']);
            });
        }

        // Add VAT fields to orders and invoices
        if (! Schema::hasColumn('orders', 'vat_rate')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->decimal('vat_rate', 5, 2)->default(0);
                $table->decimal('vat_amount', 12, 2)->default(0);
                $table->decimal('net_amount', 12, 2)->default(0);
            });
        }

        // Add VAT fields to partner statements
        if (Schema::hasTable('partner_statements')) {
            Schema::table('partner_statements', function (Blueprint $table) {
                if (! Schema::hasColumn('partner_statements', 'vat_amount')) {
                    $table->decimal('vat_amount', 12, 2)->default(0);
                }
                if (! Schema::hasColumn('partner_statements', 'net_amount')) {
                    $table->decimal('net_amount', 12, 2)->default(0);
                }
            });
        }

        // Add loyalty fields to orders
        if (! Schema::hasColumn('orders', 'loyalty_points_earned')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->integer('loyalty_points_earned')->default(0);
                $table->integer('loyalty_points_spent')->default(0);
                $table->uuid('gift_card_id')->nullable();
                $table->decimal('gift_card_amount', 12, 2)->default(0);

                $table->foreign('gift_card_id')->references('id')->on('gift_cards')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['gift_card_id']);
            $table->dropColumn([
                'vat_rate', 'vat_amount', 'net_amount',
                'loyalty_points_earned', 'loyalty_points_spent',
                'gift_card_id', 'gift_card_amount',
            ]);
        });

        Schema::table('partner_statements', function (Blueprint $table) {
            $table->dropColumn(['vat_amount', 'net_amount']);
        });

        Schema::dropIfExists('blacklists');
        Schema::dropIfExists('device_fingerprints');
        Schema::dropIfExists('risk_events');
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('gift_cards');
        Schema::dropIfExists('loyalty_ledger');
        Schema::dropIfExists('loyalty_wallets');
        Schema::dropIfExists('payout_schedules');
        Schema::dropIfExists('vat_rates');
    }
};
