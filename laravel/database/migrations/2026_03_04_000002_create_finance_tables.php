<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('game_id')->nullable();
            $table->string('stripe_payment_id', 255)->nullable();
            $table->string('type', 50)->default('empire_slot');
            $table->unsignedInteger('amount_cents');
            $table->string('currency', 3)->default('usd');
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('game_id')->references('id')->on('games');
            $table->index('game_id');
            $table->index('stripe_payment_id');
        });

        Schema::create('prize_payouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('game_id');
            $table->unsignedBigInteger('player_id');
            $table->unsignedTinyInteger('place');
            $table->unsignedInteger('amount_cents');
            $table->string('currency', 3)->default('usd');
            $table->string('status', 20)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('game_id')->references('id')->on('games');
            $table->foreign('player_id')->references('id')->on('players');
            $table->unique(['game_id', 'place']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prize_payouts');
        Schema::dropIfExists('transactions');
    }
};
