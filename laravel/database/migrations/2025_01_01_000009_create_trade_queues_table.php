<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trade_queues', function (Blueprint $table) {
            $table->id();
            $table->integer('player_id')->default(0);
            $table->integer('wood')->default(0);
            $table->integer('food')->default(0);
            $table->integer('iron')->default(0);
            $table->integer('tools')->default(0);
            $table->integer('swords')->default(0);
            $table->integer('bows')->default(0);
            $table->integer('horses')->default(0);
            $table->integer('city_id')->default(0);
            $table->integer('total_goods')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_queues');
    }
};
