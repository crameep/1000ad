<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('explore_queues', function (Blueprint $table) {
            $table->id();
            $table->integer('player_id')->default(0);
            $table->integer('turn')->default(0);
            $table->integer('people')->default(0);
            $table->bigInteger('food')->default(0);
            $table->integer('mland')->default(0);
            $table->integer('pland')->default(0);
            $table->integer('fland')->default(0);
            $table->integer('seek_land')->default(0);
            $table->timestamp('created_on')->nullable();
            $table->integer('horses')->default(0);
            $table->integer('turns_used')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('explore_queues');
    }
};
