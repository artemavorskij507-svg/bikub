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
        // Data Catalog
        if (! Schema::hasTable('data_catalog')) {
            Schema::create('data_catalog', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('entity'); // table name
                $table->string('field');
                $table->string('dtype'); // varchar, int, jsonb, etc.
                $table->boolean('pii'); // personally identifiable information
                $table->boolean('sensitive');
                $table->string('owner'); // data owner
                $table->text('description');
                $table->json('tags');
                $table->json('meta');
                $table->timestamps();

                $table->unique(['entity', 'field']);
                $table->index(['pii', 'sensitive']);
                $table->index(['owner']);
            });
        }

        // Data Lineage
        if (! Schema::hasTable('data_lineage')) {
            Schema::create('data_lineage', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('source'); // source table/field
                $table->string('target'); // target table/field
                $table->string('job'); // ETL job name
                $table->string('transformation_type'); // copy, transform, aggregate
                $table->json('transformation_logic');
                $table->timestamp('last_run');
                $table->string('status'); // success, failed, running
                $table->json('meta');
                $table->timestamps();

                $table->index(['source', 'target']);
                $table->index(['job']);
                $table->index(['last_run']);
            });
        }

        // Data Quality Checks
        if (! Schema::hasTable('dq_checks')) {
            Schema::create('dq_checks', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('entity');
                $table->string('check_name');
                $table->json('rule'); // validation rules
                $table->string('severity'); // warning, error, critical
                $table->string('last_status'); // pass, fail, warning
                $table->json('last_results');
                $table->timestamp('last_run');
                $table->boolean('blocks_import');
                $table->timestamps();

                $table->index(['entity']);
                $table->index(['severity']);
                $table->index(['last_status']);
            });
        }

        // Data Quality Results
        if (! Schema::hasTable('dq_results')) {
            Schema::create('dq_results', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('check_id');
                $table->string('status'); // pass, fail, warning
                $table->json('results');
                $table->integer('records_checked');
                $table->integer('records_failed');
                $table->text('error_message')->nullable();
                $table->timestamp('checked_at');
                $table->timestamps();

                $table->index(['check_id']);
                $table->index(['status']);
                $table->index(['checked_at']);
            });
        }

        // LMS Courses
        if (! Schema::hasTable('lms_courses')) {
            Schema::create('lms_courses', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('code')->unique();
                $table->string('title');
                $table->text('description');
                $table->json('content'); // lessons, videos, quizzes
                $table->integer('duration_minutes');
                $table->string('category'); // courier, operator, partner
                $table->string('level'); // beginner, intermediate, advanced
                $table->json('prerequisites');
                $table->boolean('is_active');
                $table->timestamps();

                $table->index(['category', 'level']);
                $table->index(['is_active']);
            });
        }

        // LMS Enrollments
        if (! Schema::hasTable('lms_enrollments')) {
            Schema::create('lms_enrollments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('course_id');
                $table->uuid('user_id');
                $table->string('status'); // enrolled, in_progress, completed, failed
                $table->integer('progress_percentage');
                $table->integer('score')->nullable();
                $table->string('certificate_url')->nullable();
                $table->timestamp('enrolled_at');
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->unique(['course_id', 'user_id']);
                $table->index(['user_id']);
                $table->index(['status']);
            });
        }

        // LMS Lessons
        if (! Schema::hasTable('lms_lessons')) {
            Schema::create('lms_lessons', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('course_id');
                $table->string('title');
                $table->text('content');
                $table->string('type'); // video, text, quiz, practical
                $table->integer('order_index');
                $table->integer('duration_minutes');
                $table->json('resources'); // files, links
                $table->timestamps();

                $table->index(['course_id', 'order_index']);
            });
        }

        // LMS Quizzes
        if (! Schema::hasTable('lms_quizzes')) {
            Schema::create('lms_quizzes', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('lesson_id');
                $table->string('question');
                $table->json('options');
                $table->string('correct_answer');
                $table->text('explanation');
                $table->integer('points');
                $table->timestamps();

                $table->index(['lesson_id']);
            });
        }

        // LMS Quiz Attempts
        if (! Schema::hasTable('lms_quiz_attempts')) {
            Schema::create('lms_quiz_attempts', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('quiz_id');
                $table->uuid('user_id');
                $table->string('answer');
                $table->boolean('is_correct');
                $table->timestamp('attempted_at');
                $table->timestamps();

                $table->index(['quiz_id', 'user_id']);
            });
        }

        // Franchise Playbooks
        if (! Schema::hasTable('franchise_playbooks')) {
            Schema::create('franchise_playbooks', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('type'); // city_launch, module_setup, operations
                $table->json('steps');
                $table->json('checklists');
                $table->json('templates');
                $table->string('version');
                $table->boolean('is_active');
                $table->timestamps();

                $table->index(['type', 'is_active']);
            });
        }

        // Playbook Executions
        if (! Schema::hasTable('playbook_executions')) {
            Schema::create('playbook_executions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('playbook_id');
                $table->uuid('tenant_id');
                $table->string('status'); // running, completed, failed
                $table->json('execution_log');
                $table->uuid('executed_by');
                $table->timestamp('started_at');
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['playbook_id']);
                $table->index(['tenant_id']);
                $table->index(['status']);
            });
        }

        // Device Firmwares
        if (! Schema::hasTable('device_firmwares')) {
            Schema::create('device_firmwares', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('model');
                $table->string('version');
                $table->string('url');
                $table->string('checksum');
                $table->json('release_notes');
                $table->boolean('is_stable');
                $table->timestamp('released_at');
                $table->timestamps();

                $table->unique(['model', 'version']);
                $table->index(['model', 'is_stable']);
            });
        }

        // Device Configurations
        if (! Schema::hasTable('device_configs')) {
            Schema::create('device_configs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('device_id');
                $table->json('config');
                $table->string('version');
                $table->boolean('active');
                $table->timestamp('applied_at')->nullable();
                $table->timestamps();

                $table->index(['device_id']);
                $table->index(['active']);
            });
        }

        // OTA Rollouts
        if (! Schema::hasTable('ota_rollouts')) {
            Schema::create('ota_rollouts', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('firmware_id');
                $table->string('name');
                $table->json('target_devices');
                $table->json('rollout_phases'); // 10%, 50%, 100%
                $table->string('status'); // scheduled, running, completed, failed
                $table->json('rollback_config');
                $table->timestamp('scheduled_at');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['firmware_id']);
                $table->index(['status']);
            });
        }

        // EDI Partners
        if (! Schema::hasTable('edi_partners')) {
            Schema::create('edi_partners', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('partner_id');
                $table->string('name');
                $table->json('formats'); // ORDERS, DESADV, INVOIC
                $table->json('endpoint');
                $table->json('secrets');
                $table->json('mapping_config');
                $table->boolean('is_active');
                $table->timestamps();

                $table->index(['partner_id']);
                $table->index(['is_active']);
            });
        }

        // EDI Jobs
        if (! Schema::hasTable('edi_jobs')) {
            Schema::create('edi_jobs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('partner_id');
                $table->string('type'); // orders, desadv, invoic
                $table->string('status'); // pending, processing, completed, failed
                $table->json('stats');
                $table->json('errors')->nullable();
                $table->text('raw_data')->nullable();
                $table->json('processed_data')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['partner_id']);
                $table->index(['type', 'status']);
            });
        }

        // Release Gates
        if (! Schema::hasTable('release_gates')) {
            Schema::create('release_gates', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('gate'); // security, performance, quality
                $table->json('threshold'); // criteria and limits
                $table->string('status'); // pass, fail, warning
                $table->string('last_build');
                $table->json('metrics');
                $table->timestamp('last_check');
                $table->timestamps();

                $table->index(['gate']);
                $table->index(['status']);
            });
        }

        // Release Gate Results
        if (! Schema::hasTable('release_gate_results')) {
            Schema::create('release_gate_results', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('gate_id');
                $table->string('build_id');
                $table->string('status');
                $table->json('metrics');
                $table->json('violations')->nullable();
                $table->timestamp('checked_at');
                $table->timestamps();

                $table->index(['gate_id']);
                $table->index(['build_id']);
                $table->index(['status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('release_gate_results');
        Schema::dropIfExists('release_gates');
        Schema::dropIfExists('edi_jobs');
        Schema::dropIfExists('edi_partners');
        Schema::dropIfExists('ota_rollouts');
        Schema::dropIfExists('device_configs');
        Schema::dropIfExists('device_firmwares');
        Schema::dropIfExists('playbook_executions');
        Schema::dropIfExists('franchise_playbooks');
        Schema::dropIfExists('lms_quiz_attempts');
        Schema::dropIfExists('lms_quizzes');
        Schema::dropIfExists('lms_lessons');
        Schema::dropIfExists('lms_enrollments');
        Schema::dropIfExists('lms_courses');
        Schema::dropIfExists('dq_results');
        Schema::dropIfExists('dq_checks');
        Schema::dropIfExists('data_lineage');
        Schema::dropIfExists('data_catalog');
    }
};
