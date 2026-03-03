<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // No default seeding needed - players register through the game
        // Uncomment to create a test admin player:
        // $this->call(TestPlayerSeeder::class);
    }
}
