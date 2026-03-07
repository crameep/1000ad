<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('explore_queues', function (Blueprint $table) {
            $table->tinyInteger('pct_mountain')->default(15)->after('seek_land');
            $table->tinyInteger('pct_forest')->default(30)->after('pct_mountain');
            $table->tinyInteger('pct_plains')->default(55)->after('pct_forest');
        });
    }

    public function down(): void
    {
        Schema::table('explore_queues', function (Blueprint $table) {
            $table->dropColumn(['pct_mountain', 'pct_forest', 'pct_plains']);
        });
    }
};
