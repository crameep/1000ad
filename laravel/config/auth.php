<?php

return [

    'defaults' => [
        'guard' => 'web',
        'passwords' => 'players',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'players',
        ],
    ],

    'providers' => [
        'players' => [
            'driver' => 'eloquent',
            'model' => App\Models\Player::class,
        ],
    ],

    'passwords' => [
        'players' => [
            'provider' => 'players',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,

];
