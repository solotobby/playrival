<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Run the migrations.  php artisan migrate:refresh --path=/database/migrations/2023_08_09_215007_create_matches_table.php
     *
     */
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('home_team_id');
            $table->unsignedBigInteger('away_team_id');
            $table->string('home_team')->default(0);
            $table->string('away_team')->default(0);
            $table->string('home_team_goals')->default(0);
            $table->string('away_team_goals')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->string('tag')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
