<?php

namespace Database\Seeders;

use App\Models\Player;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestPlayerSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = config('game.new_player');

        Player::create([
            'name' => 'Test Empire',
            'login_name' => 'admin',
            'password' => Hash::make('password'),
            'email' => 'admin@test.com',
            'civ' => 1, // Vikings
            'turn' => 1,
            'turns_free' => 12,
            'last_turn' => now(),
            'last_load' => now(),
            'created_on' => now(),
            'pland' => $defaults['pland'],
            'mland' => $defaults['mland'],
            'fland' => $defaults['fland'],
            'gold' => $defaults['gold'],
            'wood' => $defaults['wood'],
            'food' => $defaults['food'],
            'iron' => $defaults['iron'],
            'tools' => $defaults['tools'],
            'people' => $defaults['people'],
            'town_center' => $defaults['town_center'],
            'house' => $defaults['house'],
            'farmer' => $defaults['farmer'],
            'hunter' => $defaults['hunter'],
            'wood_cutter' => $defaults['wood_cutter'],
            'iron_mine' => $defaults['iron_mine'],
            'gold_mine' => $defaults['gold_mine'],
            'tool_maker' => $defaults['tool_maker'],
            'food_ratio' => 0,
            'is_admin' => 1,
            'score' => 0,
            'military_score' => 0,
            'land_score' => 0,
            'good_score' => 0,
            // All other fields default to 0 via migration
        ]);
    }
}
