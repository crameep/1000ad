<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->default('1000 A.D.');
            $table->string('slug', 50)->unique();
            $table->text('description')->nullable();
            $table->string('preset', 50)->default('standard'); // standard, blitz, tournament, custom
            $table->string('status', 20)->default('active');    // setup, active, paused, ended

            // Timing
            $table->integer('minutes_per_turn')->default(5);
            $table->integer('max_turns_stored')->default(500);
            $table->integer('start_turns')->default(100);

            // Limits
            $table->integer('max_attacks')->default(5);
            $table->integer('max_builds')->default(50);
            $table->integer('alliance_max_members')->default(10);

            // Dates
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->boolean('deathmatch_mode')->default(false);
            $table->timestamp('deathmatch_start')->nullable();

            // JSON blob for all other settings
            // (trade_prices, local_prices, wall costs, new_player defaults, population constants)
            $table->text('settings')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
