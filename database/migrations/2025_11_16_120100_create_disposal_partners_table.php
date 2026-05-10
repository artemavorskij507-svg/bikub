<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disposal_partners', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('type'); // RECYCLING_CENTER, CHARITY, HAZARDOUS_PROCESSOR, LANDFILL
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('opening_hours')->nullable();
            $table->json('accepted_categories'); // required
            $table->text('requirements')->nullable();
            $table->json('licenses')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disposal_partners');
    }
};
