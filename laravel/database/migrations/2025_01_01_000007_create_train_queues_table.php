<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('train_queues', function (Blueprint $table) {
            $table->id();
            $table->integer('player_id')->default(0);
            $table->integer('soldier_type')->default(0);
            $table->integer('turns_remaining')->default(0);
            $table->integer('qty')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('train_queues');
    }
};
