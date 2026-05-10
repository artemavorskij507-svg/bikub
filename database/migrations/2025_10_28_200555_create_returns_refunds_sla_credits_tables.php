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
        // Returns and Replacements (only create if doesn't exist)
        if (! Schema::hasTable('returns')) {
            Schema::create('returns', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->enum('type', ['return_full', 'return_partial', 'replacement']);
                $table->enum('reason', [
                    'defective', 'wrong_item', 'not_as_described', 'damaged_delivery',
                    'late_delivery', 'missing_items', 'customer_changed_mind', 'other',
                ]);
                $table->enum('status', ['pending', 'approved', 'rejected', 'processing', 'completed']);
                $table->json('items'); // Items being returned with quantities
                $table->decimal('restocking_fee', 12, 2)->default(0);
                $table->text('notes')->nullable();
                $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('processed_at')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }

        // Refunds (only create if doesn't exist)
        if (! Schema::hasTable('refunds')) {
            Schema::create('refunds', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('payment_id');
                $table->uuid('return_id')->nullable();
                $table->decimal('amount', 12, 2);
                $table->enum('type', ['full', 'partial', 'sla_credit']);
                $table->enum('reason', [
                    'return', 'replacement', 'sla_breach', 'service_issue',
                    'payment_error', 'duplicate_charge', 'other',
                ]);
                $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled']);
                $table->string('provider_ref')->nullable(); // Stripe/Vipps refund reference
                $table->text('notes')->nullable();
                $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('processed_at')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->foreign('payment_id')->references('id')->on('payments')->onDelete('cascade');
                $table->foreign('return_id')->references('id')->on('returns')->onDelete('set null');
            });
        }

        // SLA Credits (only create if doesn't exist)
        if (! Schema::hasTable('sla_credits')) {
            Schema::create('sla_credits', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->enum('metric', ['delivery_time', 'response_time', 'completion_time', 'quality_score']);
                $table->integer('delta_minutes'); // How many minutes late/early
                $table->decimal('credit_amount', 12, 2);
                $table->enum('status', ['granted', 'applied', 'expired', 'cancelled']);
                $table->foreignId('sla_policy_id')->nullable()->constrained('sla_policies')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }

        // Return Items (detailed tracking) (only create if doesn't exist)
        if (! Schema::hasTable('return_items')) {
            Schema::create('return_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('return_id');
                $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
                $table->integer('quantity');
                $table->enum('condition', ['new', 'used', 'damaged', 'defective']);
                $table->decimal('refund_amount', 10, 2);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('return_id')->references('id')->on('returns')->onDelete('cascade');
            });
        }

        // SLA Credit Applications (only create if doesn't exist)
        if (! Schema::hasTable('sla_credit_applications')) {
            Schema::create('sla_credit_applications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('sla_credit_id');
                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->decimal('applied_amount', 12, 2);
                $table->enum('status', ['pending', 'applied', 'rejected']);
                $table->timestamp('applied_at')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->foreign('sla_credit_id')->references('id')->on('sla_credits')->onDelete('cascade');
            });
        }

        // Add refund tracking to orders
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'refund_amount')) {
                $table->decimal('refund_amount', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('orders', 'sla_credit_amount')) {
                $table->decimal('sla_credit_amount', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('orders', 'refund_status')) {
                $table->enum('refund_status', ['none', 'partial', 'full'])->default('none');
            }
        });

        // Add refund tracking to payments
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('refunded_amount', 12, 2)->default(0);
            $table->enum('refund_status', ['none', 'partial', 'full'])->default('none');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['refunded_amount', 'refund_status']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['refund_amount', 'sla_credit_amount', 'refund_status']);
        });

        Schema::dropIfExists('sla_credit_applications');
        Schema::dropIfExists('return_items');
        Schema::dropIfExists('sla_credits');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('returns');
    }
};
