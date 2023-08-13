<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * * Run the migrations.  php artisan migrate:refresh --path=/database/migrations/2023_08_09_212555_create_events_table.php
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('game_type_id');
            $table->string('name');
            $table->boolean('is_owner_participate')->default(false);
            $table->boolean('is_home_away')->default(false);
            $table->boolean('is_start')->default(false);
            $table->string('code');
            $table->text('banner');
            $table->integer('number_of_teams')->default(0);
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
