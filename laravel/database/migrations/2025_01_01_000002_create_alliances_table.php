<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alliances', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('tag', 20);
            $table->integer('leader_id')->default(0);
            $table->string('password', 20)->nullable();
            $table->text('news')->nullable();
            $table->integer('ally1')->default(0);
            $table->integer('ally2')->default(0);
            $table->integer('ally3')->default(0);
            $table->integer('ally4')->default(0);
            $table->integer('ally5')->default(0);
            $table->integer('war1')->default(0);
            $table->integer('war2')->default(0);
            $table->integer('war3')->default(0);
            $table->integer('war4')->default(0);
            $table->integer('war5')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alliances');
    }
};
