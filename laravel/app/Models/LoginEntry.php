<?php

namespace App\Models;

use App\Models\Traits\BelongsToGame;
use Illuminate\Database\Eloquent\Model;

class LoginEntry extends Model
{
    use BelongsToGame;
    protected $guarded = ['id'];

    protected $casts = [
        'created_on' => 'datetime',
    ];

    public $timestamps = false;

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
