<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AidLog extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'created_on' => 'datetime',
    ];

    public $timestamps = false;

    public function sender()
    {
        return $this->belongsTo(Player::class, 'from_player_id');
    }

    public function receiver()
    {
        return $this->belongsTo(Player::class, 'to_player_id');
    }
}
