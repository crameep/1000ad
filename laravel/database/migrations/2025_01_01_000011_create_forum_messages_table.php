<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_messages', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('title', 100)->nullable();
            $table->timestamp('last_update')->nullable();
            $table->string('last_update_by', 50)->nullable();
            $table->integer('admin_only')->nullable();
            $table->text('message')->nullable();
            $table->integer('parent_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_messages');
    }
};
