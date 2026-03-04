<?php

namespace App\Models;

use App\Models\Traits\BelongsToGame;
use Illuminate\Database\Eloquent\Model;

class BlockMessage extends Model
{
    use BelongsToGame;
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
