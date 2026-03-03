<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameLog extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'created_on' => 'datetime',
    ];

    public $timestamps = false;
}
