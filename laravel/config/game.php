<?php

/**
 * 1000 A.D. Game Configuration
 *
 * Ported from Application.cfm
 * Original: Andrew Deren, (C) AderSoftware 2000, 2001
 */

return [

    'version' => '2.0.0',
    'name' => env('GAME_NAME', '1000 A.D.'),

    /*
    |--------------------------------------------------------------------------
    | Civilizations
    |--------------------------------------------------------------------------
    */
    'empires' => [
        1 => 'Vikings',
        2 => 'Franks',
        3 => 'Japanese',
        4 => 'Byzantines',
        5 => 'Mongols',
        6 => 'Incas',
    ],

    'unique_units' => [
        1 => 'Berserker',
        2 => 'Paladin',
        3 => 'Samurai',
        4 => 'Cataphract',
        5 => 'Horse Archer',
        6 => 'Shaman',
    ],

    /*
    |--------------------------------------------------------------------------
    | Timing
    |--------------------------------------------------------------------------
    */
    'minutes_per_turn' => env('GAME_MINUTES_PER_TURN', 5),
    'max_turns_stored' => env('GAME_MAX_TURNS', 500),
    'start_turns' => 100,

    /*
    |--------------------------------------------------------------------------
    | Limits
    |--------------------------------------------------------------------------
    */
    'max_attacks' => 5,
    'max_builds' => 50,
    'alliance_max_members' => env('GAME_ALLIANCE_MAX', 10),

    /*
    |--------------------------------------------------------------------------
    | Game Dates
    |--------------------------------------------------------------------------
    */
    'start_date' => env('GAME_START_DATE', '2026-01-01 09:00:00'),
    'end_date' => env('GAME_END_DATE', '2026-12-31 09:00:00'),

    /*
    |--------------------------------------------------------------------------
    | Deathmatch
    |--------------------------------------------------------------------------
    */
    'deathmatch_mode' => env('GAME_DEATHMATCH', false),
    'deathmatch_start' => env('GAME_DEATHMATCH_START', null),

    /*
    |--------------------------------------------------------------------------
    | Trading
    |--------------------------------------------------------------------------
    */
    'local_trade_multiplier' => 0.05,

    'trade_prices' => [
        'wood' => ['min' => 5, 'max' => 100],
        'food' => ['min' => 5, 'max' => 100],
        'iron' => ['min' => 20, 'max' => 300],
        'tools' => ['min' => 50, 'max' => 600],
        'maces' => ['min' => 50, 'max' => 2000],
        'swords' => ['min' => 100, 'max' => 3000],
        'bows' => ['min' => 100, 'max' => 3000],
        'horses' => ['min' => 100, 'max' => 3000],
    ],

    'local_prices' => [
        'wood' => ['sell' => 30, 'buy' => 32],
        'food' => ['sell' => 15, 'buy' => 18],
        'iron' => ['sell' => 75, 'buy' => 78],
        'tools' => ['sell' => 150, 'buy' => 180],
    ],

    /*
    |--------------------------------------------------------------------------
    | Wall Costs
    |--------------------------------------------------------------------------
    */
    'wall' => [
        'gold' => 100,
        'iron' => 1,
        'wood' => 10,
        'wine' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Population
    |--------------------------------------------------------------------------
    */
    'people_eat_one_food' => 50,
    'soldiers_eat_one_food' => 3,
    'extra_food_per_land' => 800,
    'people_burn_one_wood' => 250,

    /*
    |--------------------------------------------------------------------------
    | New Player Defaults
    |--------------------------------------------------------------------------
    */
    'new_player' => [
        'tool_maker' => 10,
        'wood_cutter' => 20,
        'gold_mine' => 10,
        'hunter' => 50,
        'tower' => 10,
        'town_center' => 10,
        'market' => 10,
        'iron_mine' => 20,
        'house' => 50,
        'farmer' => 20,
        'fland' => 1000,
        'mland' => 500,
        'pland' => 2500,
        'swordsman' => 3,
        'archers' => 3,
        'horseman' => 3,
        'people' => 3000,
        'wood' => 1000,
        'food' => 2500,
        'iron' => 1000,
        'gold' => 100000,
        'tools' => 250,
        'food_ratio' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Game Presets
    |--------------------------------------------------------------------------
    | Default values for game presets when creating new games.
    */
    'presets' => [
        'standard' => [
            'label' => 'Standard',
            'description' => 'Classic gameplay with standard turn speed.',
            'minutes_per_turn' => 5,
            'max_turns_stored' => 500,
            'start_turns' => 100,
            'max_attacks' => 5,
            'max_builds' => 50,
            'alliance_max_members' => 10,
            'deathmatch_mode' => false,
        ],
        'blitz' => [
            'label' => 'Blitz',
            'description' => 'Fast-paced game with rapid turns.',
            'minutes_per_turn' => 1,
            'max_turns_stored' => 1000,
            'start_turns' => 200,
            'max_attacks' => 10,
            'max_builds' => 100,
            'alliance_max_members' => 10,
            'deathmatch_mode' => false,
        ],
        'tournament' => [
            'label' => 'Tournament',
            'description' => 'Competitive game with moderate turn speed.',
            'minutes_per_turn' => 3,
            'max_turns_stored' => 300,
            'start_turns' => 50,
            'max_attacks' => 5,
            'max_builds' => 50,
            'alliance_max_members' => 6,
            'deathmatch_mode' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Mail
    |--------------------------------------------------------------------------
    */
    'admin_email' => env('GAME_ADMIN_EMAIL', 'admin@1000ad.net'),
];
