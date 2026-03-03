<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockMessage extends Model
{
    protected $guarded = ['id'];

    public $timestamps = false;

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function blockedPlayer()
    {
        return $this->belongsTo(Player::class, 'block_player_id');
    }
}
