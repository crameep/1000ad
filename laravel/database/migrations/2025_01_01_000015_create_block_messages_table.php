<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('block_messages', function (Blueprint $table) {
            $table->id();
            $table->integer('player_id')->nullable();
            $table->integer('block_player_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('block_messages');
    }
};
