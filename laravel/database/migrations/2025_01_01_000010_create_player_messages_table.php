<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_messages', function (Blueprint $table) {
            $table->id();
            $table->integer('from_player_id')->default(0);
            $table->integer('to_player_id')->default(0);
            $table->string('from_player_name', 50)->nullable();
            $table->string('to_player_name', 50)->nullable();
            $table->text('message')->nullable();
            $table->integer('viewed')->default(0);
            $table->timestamp('created_on')->nullable();
            $table->integer('message_type')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_messages');
    }
};
