<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.  php artisan migrate:refresh --path=/database/migrations/2024_02_08_190114_create_otps_table.php
     * docker-compose run --rm artisan migrate:refresh --path=/database/migrations/2024_02_08_190114_create_otps_table.php
     */
    public function up(): void
    {
        Schema::create('otps', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->string('email')->index();
            $table->string('otp')->default('');
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otps');
    }
};
