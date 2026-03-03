<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attack_queues', function (Blueprint $table) {
            $table->id();
            $table->integer('player_id')->default(0);
            $table->integer('attack_player_id')->default(0);
            $table->integer('swordsman')->default(0);
            $table->integer('archers')->default(0);
            $table->integer('horseman')->default(0);
            $table->integer('catapults')->default(0);
            $table->integer('macemen')->default(0);
            $table->integer('trained_peasants')->default(0);
            $table->integer('thieves')->default(0);
            $table->integer('uunit')->default(0);
            $table->integer('turn')->default(0);
            $table->integer('status')->default(0);
            $table->integer('attack_type')->default(0);
            $table->integer('cost_wine')->default(0);
            $table->integer('cost_food')->default(0);
            $table->integer('cost_gold')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attack_queues');
    }
};
