<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'stripe_connect_account_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('stripe_connect_account_id', 255)->nullable()->after('stripe_customer_id');
            });
        }

        if (!Schema::hasColumn('prize_payouts', 'stripe_transfer_id')) {
            Schema::table('prize_payouts', function (Blueprint $table) {
                $table->string('stripe_transfer_id', 255)->nullable()->after('notes');
                $table->index('stripe_transfer_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'stripe_connect_account_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('stripe_connect_account_id');
            });
        }

        if (Schema::hasColumn('prize_payouts', 'stripe_transfer_id')) {
            Schema::table('prize_payouts', function (Blueprint $table) {
                $table->dropIndex(['stripe_transfer_id']);
                $table->dropColumn('stripe_transfer_id');
            });
        }
    }
};
