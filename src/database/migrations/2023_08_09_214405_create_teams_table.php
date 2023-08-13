<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * * Run the migrations.  php artisan migrate --path=/database/migrations/2023_08_09_214405_create_teams_table.php
     * 
     *  * * Run the migrations.  php artisan migrate:refresh --path=/database/migrations/2023_08_09_214405_create_teams_table.php
     */
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->text('logo')->nullable();
            $table->string('team_type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
