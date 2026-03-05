<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('train_queues', 'pos')) {
            Schema::table('train_queues', function (Blueprint $table) {
                $table->integer('pos')->default(0)->after('qty');
            });
        }
    }

    public function down(): void
    {
        Schema::table('train_queues', function (Blueprint $table) {
            $table->dropColumn('pos');
        });
    }
};
