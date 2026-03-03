<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_players', function (Blueprint $table) {
            $table->id();
            $table->integer('player_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_players');
    }
};
