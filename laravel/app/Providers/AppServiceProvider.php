<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Load game helper functions (player(), activeGame(), gameConfig())
        require_once app_path('Helpers/game.php');
    }

    public function boot(): void
    {
        //
    }
}
