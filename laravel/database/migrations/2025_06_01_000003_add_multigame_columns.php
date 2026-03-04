<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables that need a game_id column (all game-scoped tables).
     */
    protected array $gameIdTables = [
        'players',
        'alliances',
        'attack_news',
        'attack_queues',
        'build_queues',
        'explore_queues',
        'train_queues',
        'transfer_queues',
        'trade_queues',
        'player_messages',
        'forum_messages',
        'game_logs',
        'auto_local_trades',
        'block_messages',
        'ai_players',
        'login_entries',
        'aid_logs',
    ];

    public function up(): void
    {
        // Step 1: Add user_id to players
        Schema::table('players', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->unsignedBigInteger('game_id')->default(1)->after('user_id');
            $table->index(['game_id']);
            $table->index(['user_id', 'game_id']);
        });

        // Step 2: Add game_id to all other game-scoped tables
        foreach ($this->gameIdTables as $tableName) {
            if ($tableName === 'players') {
                continue; // Already handled above
            }

            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->unsignedBigInteger('game_id')->default(1)->after('id');
                $table->index('game_id');
            });
        }

        // Step 3: Populate users from existing players
        $players = DB::table('players')
            ->select('login_name', 'password', 'email', 'is_admin', 'created_on')
            ->get();

        $userMap = []; // login_name => user_id
        foreach ($players as $player) {
            // Skip if we already inserted this login_name (duplicates)
            if (isset($userMap[$player->login_name])) {
                continue;
            }

            $userId = DB::table('users')->insertGetId([
                'login_name' => $player->login_name,
                'password' => $player->password,
                'email' => $player->email,
                'is_admin' => $player->is_admin ?? 0,
                'created_at' => $player->created_on ?? now(),
                'updated_at' => now(),
            ]);

            $userMap[$player->login_name] = $userId;
        }

        // Step 4: Create Game #1 from current config
        $settings = [
            'local_trade_multiplier' => config('game.local_trade_multiplier', 0.05),
            'trade_prices' => config('game.trade_prices', []),
            'local_prices' => config('game.local_prices', []),
            'wall' => config('game.wall', []),
            'people_eat_one_food' => config('game.people_eat_one_food', 50),
            'soldiers_eat_one_food' => config('game.soldiers_eat_one_food', 3),
            'extra_food_per_land' => config('game.extra_food_per_land', 800),
            'people_burn_one_wood' => config('game.people_burn_one_wood', 250),
            'new_player' => config('game.new_player', []),
        ];

        DB::table('games')->insert([
            'id' => 1,
            'name' => config('game.name', '1000 A.D.'),
            'slug' => 'standard-1',
            'description' => 'The original 1000 A.D. game',
            'preset' => 'standard',
            'status' => 'active',
            'minutes_per_turn' => config('game.minutes_per_turn', 5),
            'max_turns_stored' => config('game.max_turns_stored', 500),
            'start_turns' => config('game.start_turns', 100),
            'max_attacks' => config('game.max_attacks', 5),
            'max_builds' => config('game.max_builds', 50),
            'alliance_max_members' => config('game.alliance_max_members', 10),
            'start_date' => config('game.start_date', '2026-01-01 09:00:00'),
            'end_date' => config('game.end_date', '2026-12-31 09:00:00'),
            'deathmatch_mode' => config('game.deathmatch_mode', false),
            'deathmatch_start' => config('game.deathmatch_start'),
            'settings' => json_encode($settings),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Step 5: Set players.user_id from users lookup
        foreach ($userMap as $loginName => $userId) {
            DB::table('players')
                ->where('login_name', $loginName)
                ->update(['user_id' => $userId]);
        }

        // Step 6: All records already have game_id=1 via default value
    }

    public function down(): void
    {
        // Remove user_id and game_id from players
        Schema::table('players', function (Blueprint $table) {
            $table->dropIndex(['game_id']);
            $table->dropIndex(['user_id', 'game_id']);
            $table->dropColumn(['user_id', 'game_id']);
        });

        // Remove game_id from all other tables
        foreach ($this->gameIdTables as $tableName) {
            if ($tableName === 'players') {
                continue;
            }

            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex(['game_id']);
                $table->dropColumn('game_id');
            });
        }

        // Remove migrated users and game
        DB::table('users')->truncate();
        DB::table('games')->truncate();
    }
};
