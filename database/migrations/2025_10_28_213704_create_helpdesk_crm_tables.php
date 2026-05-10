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
        // Support Tickets
        if (! Schema::hasTable('support_tickets')) {
            Schema::create('support_tickets', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('number')->unique(); // TKT-2025-001
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('email')->nullable();
                $table->string('name')->nullable();
                $table->string('phone')->nullable();
                $table->enum('source', ['web_form', 'email', 'phone', 'chat', 'api'])->default('web_form');
                $table->string('subject');
                $table->text('description');
                $table->enum('status', ['new', 'open', 'pending', 'resolved', 'closed'])->default('new');
                $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
                $table->uuid('category_id')->nullable();
                $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('sla_due_at')->nullable();
                $table->timestamp('first_response_at')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->json('custom_fields')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index(['status', 'priority', 'created_at']);
                $table->index(['assignee_id', 'status']);
                $table->index(['email', 'created_at']);
            });
        }

        $ticketIdType = Schema::hasColumn('support_tickets', 'id')
            ? Schema::getColumnType('support_tickets', 'id')
            : 'uuid';

        $addTicketForeignKey = function (Blueprint $table) use ($ticketIdType) {
            if ($ticketIdType === 'uuid') {
                $table->uuid('ticket_id');
            } else {
                $table->unsignedBigInteger('ticket_id');
            }

            $table
                ->foreign('ticket_id')
                ->references('id')
                ->on('support_tickets')
                ->onDelete('cascade');
        };

        // Ticket Categories
        if (! Schema::hasTable('ticket_categories')) {
            Schema::create('ticket_categories', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->uuid('parent_id')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('active')->default(true);
                $table->json('sla_settings')->nullable(); // SLA rules for this category
                $table->json('auto_assignment')->nullable(); // Auto-assignment rules
                $table->json('meta')->nullable();
                $table->timestamps();

                // Removed self-referencing FK on parent_id for PostgreSQL compatibility
                $table->index(['active', 'sort_order']);
            });
        }

        // Ticket Messages
        if (! Schema::hasTable('ticket_messages')) {
            Schema::create('ticket_messages', function (Blueprint $table) use ($addTicketForeignKey) {
                $table->uuid('id')->primary();
                $addTicketForeignKey($table);
                $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('type', ['message', 'note', 'system'])->default('message');
                $table->enum('direction', ['inbound', 'outbound'])->default('inbound');
                $table->json('content'); // Rich text content
                $table->json('attachments')->nullable(); // File attachments
                $table->boolean('is_internal')->default(false); // Internal note
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index(['ticket_id', 'created_at']);
                $table->index(['author_id', 'created_at']);
            });
        }

        // Ticket Tags
        if (! Schema::hasTable('ticket_tags')) {
            Schema::create('ticket_tags', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name')->unique();
                $table->string('color')->default('#3B82F6');
                $table->text('description')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }

        // Ticket-Tag Mapping
        if (! Schema::hasTable('ticket_tag_map')) {
            Schema::create('ticket_tag_map', function (Blueprint $table) use ($addTicketForeignKey) {
                $table->uuid('id')->primary();
                $addTicketForeignKey($table);
                $table->uuid('tag_id');
                $table->timestamps();

                $table->foreign('tag_id')->references('id')->on('ticket_tags')->onDelete('cascade');
                $table->unique(['ticket_id', 'tag_id']);
            });
        }

        // Response Templates
        if (! Schema::hasTable('response_templates')) {
            Schema::create('response_templates', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('subject');
                $table->json('content'); // Rich text template
                $table->uuid('category_id')->nullable();
                $table->string('locale', 5)->default('en');
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->json('variables')->nullable(); // Available template variables
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->foreign('category_id')->references('id')->on('ticket_categories')->onDelete('set null');
                $table->index(['category_id', 'locale', 'is_active']);
            });
        }

        // SLA Policies
        if (! Schema::hasTable('sla_policies')) {
            Schema::create('sla_policies', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->text('description')->nullable();
                $table->uuid('category_id')->nullable();
                $table->enum('priority', ['low', 'normal', 'high', 'urgent']);
                $table->integer('first_response_minutes')->nullable();
                $table->integer('resolution_minutes')->nullable();
                $table->json('business_hours')->nullable(); // Business hours configuration
                $table->json('holidays')->nullable(); // Holiday calendar
                $table->boolean('is_active')->default(true);
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->foreign('category_id')->references('id')->on('ticket_categories')->onDelete('set null');
                $table->index(['category_id', 'priority', 'is_active']);
            });
        }

        // Ticket Escalations
        if (! Schema::hasTable('ticket_escalations')) {
            Schema::create('ticket_escalations', function (Blueprint $table) use ($addTicketForeignKey) {
                $table->uuid('id')->primary();
                $addTicketForeignKey($table);
                $table->enum('reason', ['sla_breach', 'manual', 'auto'])->default('auto');
                $table->foreignId('escalated_to')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
                $table->timestamp('escalated_at');
                $table->timestamp('resolved_at')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index(['ticket_id', 'escalated_at']);
            });
        }

        // Ticket Satisfaction Surveys
        if (! Schema::hasTable('ticket_satisfaction')) {
            Schema::create('ticket_satisfaction', function (Blueprint $table) use ($addTicketForeignKey) {
                $table->uuid('id')->primary();
                $addTicketForeignKey($table);
                $table->integer('rating')->nullable(); // 1-5 stars
                $table->text('feedback')->nullable();
                $table->json('survey_responses')->nullable();
                $table->timestamp('survey_sent_at')->nullable();
                $table->timestamp('survey_completed_at')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index(['ticket_id', 'survey_completed_at']);
            });
        }

        // Email Integration
        if (! Schema::hasTable('email_integrations')) {
            Schema::create('email_integrations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('email_address')->unique();
                $table->string('name');
                $table->enum('type', ['support', 'sales', 'general'])->default('support');
                $table->uuid('default_category_id')->nullable();
                $table->foreignId('default_assignee_id')->nullable()->constrained('users')->nullOnDelete();
                $table->json('imap_settings')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_sync_at')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->foreign('default_category_id')->references('id')->on('ticket_categories')->onDelete('set null');
            });
        }

        // Ticket Statistics
        if (! Schema::hasTable('ticket_stats')) {
            Schema::create('ticket_stats', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->date('date');
                $table->uuid('category_id')->nullable();
                $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
                $table->integer('tickets_created')->default(0);
                $table->integer('tickets_resolved')->default(0);
                $table->integer('tickets_closed')->default(0);
                $table->decimal('avg_first_response_time', 8, 2)->nullable(); // minutes
                $table->decimal('avg_resolution_time', 8, 2)->nullable(); // minutes
                $table->decimal('satisfaction_score', 3, 2)->nullable(); // 1-5
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->foreign('category_id')->references('id')->on('ticket_categories')->onDelete('set null');
                $table->unique(['date', 'category_id', 'assignee_id']);
            });
        }

        // Knowledge Base Integration
        if (! Schema::hasTable('kb_ticket_links')) {
            Schema::create('kb_ticket_links', function (Blueprint $table) use ($addTicketForeignKey) {
                $table->uuid('id')->primary();
                $addTicketForeignKey($table);
                $table->uuid('kb_article_id');
                $table->enum('type', ['suggested', 'linked', 'helpful'])->default('suggested');
                $table->timestamps();

                $table->foreign('kb_article_id')->references('id')->on('kb_articles')->onDelete('cascade');
                $table->index(['ticket_id', 'type']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kb_ticket_links');
        Schema::dropIfExists('ticket_stats');
        Schema::dropIfExists('email_integrations');
        Schema::dropIfExists('ticket_satisfaction');
        Schema::dropIfExists('ticket_escalations');
        Schema::dropIfExists('sla_policies');
        Schema::dropIfExists('response_templates');
        Schema::dropIfExists('ticket_tag_map');
        Schema::dropIfExists('ticket_tags');
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('ticket_categories');
        Schema::dropIfExists('support_tickets');
    }
};
