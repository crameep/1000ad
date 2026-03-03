<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginEntry extends Model
{
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
