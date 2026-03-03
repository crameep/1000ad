<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auto_local_trades', function (Blueprint $table) {
            $table->id();
            $table->integer('trade_wood')->default(0);
            $table->integer('trade_iron')->default(0);
            $table->integer('trade_food')->default(0);
            $table->integer('trade_tools')->default(0);
            $table->integer('trade_type')->default(0);
            $table->integer('player_id')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_local_trades');
    }
};
