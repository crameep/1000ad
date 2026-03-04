<?php

/**
 * Game Helper Functions
 *
 * Global helpers for multi-game support, replacing Auth::user() and config('game.*') calls.
 */

if (!function_exists('player')) {
    /**
     * Get the current player in the active game.
     * Replaces Auth::user() in game controllers.
     *
     * @return \App\Models\Player|null
     */
    function player(): ?\App\Models\Player
    {
        return app()->bound('current_player') ? app('current_player') : null;
    }
}

if (!function_exists('activeGame')) {
    /**
     * Get the current active Game model.
     *
     * @return \App\Models\Game|null
     */
    function activeGame(): ?\App\Models\Game
    {
        return app()->bound('current_game') ? app('current_game') : null;
    }
}

if (!function_exists('gameConfig')) {
    /**
     * Get a game-specific configuration value.
     * Replaces config('game.*') calls throughout the codebase.
     *
     * Checks in order:
     * 1. Direct column on the Game model (minutes_per_turn, dates, limits, etc.)
     * 2. JSON settings on the Game model (trade_prices, wall costs, etc.)
     * 3. Fallback to config/game.php defaults
     *
     * @param string $key Config key (e.g., 'minutes_per_turn', 'trade_prices', 'wall')
     * @param mixed $default Fallback value if key not found anywhere
     * @return mixed
     */
    function gameConfig(string $key, $default = null)
    {
        $game = activeGame();

        // If no active game, fall back to config file entirely
        if (!$game) {
            return config("game.{$key}", $default);
        }

        // Direct columns on the Game model
        static $directColumns = [
            'name', 'slug', 'description', 'preset', 'status',
            'minutes_per_turn', 'max_turns_stored', 'start_turns',
            'max_attacks', 'max_builds', 'alliance_max_members',
            'start_date', 'end_date',
            'deathmatch_mode', 'deathmatch_start',
        ];

        if (in_array($key, $directColumns)) {
            $value = $game->{$key};
            return $value !== null ? $value : config("game.{$key}", $default);
        }

        // Static config keys that never change per game (civilizations, unique units)
        static $staticKeys = ['empires', 'unique_units', 'version', 'admin_email'];
        if (in_array($key, $staticKeys)) {
            return config("game.{$key}", $default);
        }

        // JSON settings on the Game model, falling back to config file
        $settingValue = $game->setting($key);
        if ($settingValue !== null) {
            return $settingValue;
        }

        return config("game.{$key}", $default);
    }
}

if (!function_exists('buildingIcon')) {
    /**
     * Get the icon URL for a building by its definition array.
     * Falls back to placeholder if the icon file doesn't exist.
     */
    function buildingIcon(array $building): string
    {
        $filename = $building['db_column'] . '.png';
        $path = "images/icons/buildings/{$filename}";

        if (file_exists(public_path($path))) {
            return asset($path);
        }

        return asset('images/icons/placeholder.svg');
    }
}

if (!function_exists('resourceIcon')) {
    /**
     * Get the icon URL for a resource type.
     */
    function resourceIcon(string $resource): string
    {
        $path = "images/icons/resources/{$resource}.png";
        if (file_exists(public_path($path))) {
            return asset($path);
        }
        return '';
    }
}

if (!function_exists('landIcon')) {
    /**
     * Get the icon URL for a land type.
     */
    function landIcon(string $type): string
    {
        $path = "images/icons/land/{$type}.png";
        if (file_exists(public_path($path))) {
            return asset($path);
        }
        return '';
    }
}

if (!function_exists('ordinal')) {
    /**
     * Convert a number to its ordinal string (1st, 2nd, 3rd, etc.).
     */
    function ordinal(int $n): string
    {
        $s = ['th', 'st', 'nd', 'rd'];
        $v = $n % 100;
        return $n . ($s[($v - 20) % 10] ?? $s[$v] ?? $s[0]);
    }
}

if (!function_exists('soldierIcon')) {
    /**
     * Get the icon URL for a soldier by type index and optional civ ID.
     * Unique units (type 9) use civ-specific icons.
     */
    function soldierIcon(array $soldier, int $typeIndex, ?int $civId = null): string
    {
        if ($typeIndex === 9 && $civId !== null) {
            $uniqueMap = [
                1 => 'berserker', 2 => 'paladin', 3 => 'samurai',
                4 => 'cataphract', 5 => 'horse_archer', 6 => 'shaman',
            ];
            $filename = ($uniqueMap[$civId] ?? 'placeholder') . '.png';
            $path = "images/icons/soldiers/unique/{$filename}";
        } else {
            $nameMap = [
                'archers' => 'archer', 'swordsman' => 'swordsman',
                'horseman' => 'horseman', 'tower' => 'tower_defense',
                'catapults' => 'catapult', 'macemen' => 'macemen',
                'trained_peasants' => 'trained_peasant', 'thieves' => 'thieves',
            ];
            $filename = ($nameMap[$soldier['db_name']] ?? 'placeholder') . '.png';
            $path = "images/icons/soldiers/{$filename}";
        }

        if (file_exists(public_path($path))) {
            return asset($path);
        }

        return asset('images/icons/placeholder.svg');
    }
}
