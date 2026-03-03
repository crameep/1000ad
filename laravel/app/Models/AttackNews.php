<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttackNews extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'created_on' => 'datetime',
    ];

    public $timestamps = false;

    public function attacker()
    {
        return $this->belongsTo(Player::class, 'attack_id');
    }

    public function defender()
    {
        return $this->belongsTo(Player::class, 'defense_id');
    }
}
