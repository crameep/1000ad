<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttackQueue extends Model
{
    protected $guarded = ['id'];

    public $timestamps = false;

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function target()
    {
        return $this->belongsTo(Player::class, 'attack_player_id');
    }
}
