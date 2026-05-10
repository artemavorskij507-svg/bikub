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
        // Reviews and Ratings
        Schema::create('reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('target_type', ['order', 'partner', 'courier', 'service']);
            $table->uuid('target_id');
            $table->integer('rating')->unsigned(); // 1-5 stars
            $table->text('text')->nullable();
            $table->json('tags')->nullable(); // ['fast', 'professional', 'friendly', etc.]
            $table->enum('status', ['pending', 'published', 'hidden', 'flagged']);
            $table->boolean('is_verified')->default(false);
            $table->integer('helpful_count')->default(0);
            $table->integer('report_count')->default(0);
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
            $table->index(['status', 'created_at']);
        });

        // NPS Surveys
        Schema::create('nps_surveys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->integer('score'); // 0-10 NPS score
            $table->text('comment')->nullable();
            $table->enum('category', ['promoter', 'passive', 'detractor']);
            $table->timestamp('sent_at');
            $table->timestamp('answered_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        // Disputes
        Schema::create('disputes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', [
                'late_delivery', 'quality_issue', 'missing_items', 'damaged_goods',
                'payment_dispute', 'service_not_provided', 'wrong_item', 'other',
            ]);
            $table->enum('status', [
                'open', 'investigating', 'waiting_customer', 'waiting_partner',
                'resolved', 'closed', 'escalated',
            ]);
            $table->enum('priority', ['low', 'medium', 'high', 'urgent']);
            $table->text('description');
            $table->json('timeline')->default('[]'); // Timeline of events
            $table->json('resolution')->nullable(); // Resolution details
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        // Dispute Evidence
        Schema::create('dispute_evidence', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('dispute_id');
            $table->enum('type', ['photo', 'video', 'document', 'chat_log', 'other']);
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type');
            $table->integer('file_size');
            $table->text('description')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->foreign('dispute_id')->references('id')->on('disputes')->onDelete('cascade');
        });

        // Dispute Messages
        Schema::create('dispute_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('dispute_id');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->boolean('is_internal')->default(false);
            $table->json('attachments')->nullable();
            $table->timestamps();

            $table->foreign('dispute_id')->references('id')->on('disputes')->onDelete('cascade');
        });

        // Review Helpfulness
        Schema::create('review_helpfulness', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('review_id');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_helpful');
            $table->timestamps();

            $table->foreign('review_id')->references('id')->on('reviews')->onDelete('cascade');

            $table->unique(['review_id', 'user_id']);
        });

        // Review Reports
        Schema::create('review_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('review_id');
            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete();
            $table->enum('reason', [
                'spam', 'inappropriate', 'fake', 'offensive',
                'irrelevant', 'duplicate', 'other',
            ]);
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'reviewed', 'dismissed', 'action_taken']);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('review_id')->references('id')->on('reviews')->onDelete('cascade');
        });

        // Add review fields to orders
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('review_requested')->default(false);
            $table->timestamp('review_requested_at')->nullable();
            $table->boolean('review_completed')->default(false);
            $table->timestamp('review_completed_at')->nullable();
            $table->decimal('customer_rating', 3, 2)->nullable(); // Average rating
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'review_requested', 'review_requested_at',
                'review_completed', 'review_completed_at', 'customer_rating',
            ]);
        });

        Schema::dropIfExists('review_reports');
        Schema::dropIfExists('review_helpfulness');
        Schema::dropIfExists('dispute_messages');
        Schema::dropIfExists('dispute_evidence');
        Schema::dropIfExists('disputes');
        Schema::dropIfExists('nps_surveys');
        Schema::dropIfExists('reviews');
    }
};
