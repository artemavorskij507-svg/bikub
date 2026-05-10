<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('partner_contracts')) {
            return;
        }

        Schema::create('partner_contracts', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->bigInteger('partner_id')->index();
            $t->string('code')->unique();
            $t->enum('status', ['draft', 'active', 'suspended', 'expired'])->default('draft')->index();
            $t->date('valid_from')->nullable();
            $t->date('valid_to')->nullable()->index();
            $t->date('insurance_valid_to')->nullable();
            $t->json('terms')->nullable();
            $t->string('document_path')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_contracts');
    }
};
