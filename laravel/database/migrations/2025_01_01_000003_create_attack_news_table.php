<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attack_news', function (Blueprint $table) {
            $table->id();
            $table->integer('attack_id')->default(0);
            $table->integer('defense_id')->default(0);
            $table->integer('attack_swordsman')->default(0);
            $table->integer('attack_horseman')->default(0);
            $table->integer('attack_archers')->default(0);
            $table->integer('attack_macemen')->default(0);
            $table->integer('attack_catapults')->default(0);
            $table->integer('attack_peasants')->default(0);
            $table->integer('attack_thieves')->default(0);
            $table->integer('attack_uunit')->default(0);
            $table->integer('defense_swordsman')->default(0);
            $table->integer('defense_horseman')->default(0);
            $table->integer('defense_archers')->default(0);
            $table->integer('defense_macemen')->default(0);
            $table->integer('defense_catapults')->default(0);
            $table->integer('defense_peasants')->default(0);
            $table->integer('defense_thieves')->default(0);
            $table->integer('defense_uunit')->default(0);
            $table->text('message')->nullable();
            $table->timestamp('created_on')->nullable();
            $table->integer('attacker_wins')->default(0);
            $table->integer('deleted')->default(0);
            $table->string('attack_alliance', 50)->nullable();
            $table->string('defense_alliance', 50)->nullable();
            $table->integer('attack_alliance_id')->default(0);
            $table->integer('defense_alliance_id')->default(0);
            $table->integer('attack_type')->default(0);
            $table->text('battle_details')->nullable();
            $table->text('debug_info')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attack_news');
    }
};
