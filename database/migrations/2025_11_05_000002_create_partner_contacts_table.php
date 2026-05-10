<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('partner_contacts')) {
            return;
        }

        Schema::create('partner_contacts', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->bigInteger('partner_id')->index();
            $t->string('full_name');
            $t->string('role')->nullable();
            $t->string('email')->nullable();
            $t->string('phone_e164')->nullable();
            $t->boolean('is_primary')->default(false);
            $t->json('notify')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_contacts');
    }
};
