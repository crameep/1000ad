<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_queues', function (Blueprint $table) {
            $table->id();
            $table->integer('from_player_id')->default(0);
            $table->integer('to_player_id')->default(0);
            $table->integer('wood')->default(0);
            $table->integer('food')->default(0);
            $table->integer('iron')->default(0);
            $table->integer('gold')->default(0);
            $table->integer('swords')->default(0);
            $table->integer('bows')->default(0);
            $table->integer('horses')->default(0);
            $table->integer('tools')->default(0);
            $table->integer('maces')->default(0);
            $table->integer('transfer_type')->default(0);
            $table->integer('turns_remaining')->default(0);
            $table->integer('wood_price')->default(0);
            $table->integer('iron_price')->default(0);
            $table->integer('food_price')->default(0);
            $table->integer('tools_price')->default(0);
            $table->integer('swords_price')->default(0);
            $table->integer('bows_price')->default(0);
            $table->integer('horses_price')->default(0);
            $table->integer('maces_price')->default(0);
            $table->timestamp('created_on')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_queues');
    }
};
