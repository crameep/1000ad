<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aid_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('from_player_id')->nullable();
            $table->integer('to_player_id')->nullable();
            $table->integer('wood')->nullable();
            $table->integer('food')->nullable();
            $table->integer('iron')->nullable();
            $table->integer('gold')->nullable();
            $table->integer('swords')->nullable();
            $table->integer('bows')->nullable();
            $table->integer('horses')->nullable();
            $table->integer('tools')->nullable();
            $table->integer('maces')->nullable();
            $table->timestamp('created_on')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aid_logs');
    }
};
