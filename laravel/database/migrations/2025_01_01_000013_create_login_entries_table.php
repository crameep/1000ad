<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_entries', function (Blueprint $table) {
            $table->id();
            $table->timestamp('created_on')->nullable();
            $table->integer('player_id')->nullable();
            $table->string('ip_address', 20)->nullable();
            $table->string('http_referer', 50)->nullable();
            $table->string('http_user_agent', 50)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_entries');
    }
};
