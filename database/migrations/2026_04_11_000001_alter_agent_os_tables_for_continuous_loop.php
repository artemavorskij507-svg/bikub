<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_runs', function (Blueprint $table): void {
            if (! Schema::hasColumn('agent_runs', 'terminal_reason')) {
                $table->string('terminal_reason', 120)->nullable()->index();
            }
        });

        Schema::table('agent_steps', function (Blueprint $table): void {
            if (! Schema::hasColumn('agent_steps', 'reduced_confidence')) {
                $table->boolean('reduced_confidence')->default(false)->index();
            }

            if (! Schema::hasColumn('agent_steps', 'confidence_reason')) {
                $table->text('confidence_reason')->nullable();
            }

            if (! Schema::hasColumn('agent_steps', 'validation_result')) {
                $table->string('validation_result', 20)->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('agent_steps', function (Blueprint $table): void {
            if (Schema::hasColumn('agent_steps', 'validation_result')) {
                $table->dropColumn('validation_result');
            }

            if (Schema::hasColumn('agent_steps', 'confidence_reason')) {
                $table->dropColumn('confidence_reason');
            }

            if (Schema::hasColumn('agent_steps', 'reduced_confidence')) {
                $table->dropColumn('reduced_confidence');
            }
        });

        Schema::table('agent_runs', function (Blueprint $table): void {
            if (Schema::hasColumn('agent_runs', 'terminal_reason')) {
                $table->dropColumn('terminal_reason');
            }
        });
    }
};

