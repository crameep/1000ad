<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('empire_slots')) {
            return;
        }

        Schema::create('empire_slots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('game_id');
            $table->unsignedTinyInteger('extra_slots')->default(1);
            $table->string('stripe_payment_id', 255)->nullable();
            $table->string('stripe_customer_id', 255)->nullable();
            $table->timestamp('purchased_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'game_id']);
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('game_id')->references('id')->on('games');
        });

        if (!Schema::hasColumn('users', 'stripe_customer_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('stripe_customer_id', 255)->nullable()->after('is_admin');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('empire_slots');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('stripe_customer_id');
        });
    }
};
