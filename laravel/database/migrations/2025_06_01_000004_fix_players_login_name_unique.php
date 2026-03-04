<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the unique index on players.login_name.
        // With multi-game support, the same user (same login_name) can have
        // players in multiple games. Auth now uses the users table.
        DB::statement('DROP INDEX IF EXISTS players_login_name_unique');
    }

    public function down(): void
    {
        DB::statement('CREATE UNIQUE INDEX players_login_name_unique ON players (login_name)');
    }
};
