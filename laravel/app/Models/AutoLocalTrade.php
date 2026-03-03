<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutoLocalTrade extends Model
{
    protected $guarded = ['id'];

    public $timestamps = false;

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
