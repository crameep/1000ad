<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('build_queues', function (Blueprint $table) {
            $table->id();
            $table->integer('player_id')->default(0);
            $table->integer('turn_added')->default(0);
            $table->integer('iron')->default(0);
            $table->integer('wood')->default(0);
            $table->integer('gold')->default(0);
            $table->integer('building_no')->default(0);
            $table->integer('mission')->default(0);
            $table->integer('pos')->default(0);
            $table->integer('qty')->default(0);
            $table->integer('time_needed')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('build_queues');
    }
};
