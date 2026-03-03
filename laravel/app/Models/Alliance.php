<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alliance extends Model
{
    protected $guarded = ['id'];

    public $timestamps = true;

    public function members()
    {
        return $this->hasMany(Player::class, 'alliance_id');
    }

    public function leader()
    {
        return $this->belongsTo(Player::class, 'leader_id');
    }
}
