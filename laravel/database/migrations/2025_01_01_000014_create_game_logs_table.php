<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_logs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('created_on')->nullable();
            $table->integer('num_on')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_logs');
    }
};
