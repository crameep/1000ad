<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Player extends Authenticatable
{
    protected $guarded = ['id'];

    protected $casts = [
        'last_turn' => 'datetime',
        'last_load' => 'datetime',
        'last_attack' => 'datetime',
        'created_on' => 'datetime',
        'has_new_messages' => 'boolean',
    ];

    // Don't use Laravel's default timestamps - we have custom ones
    public $timestamps = true;

    public function alliance()
    {
        return $this->belongsTo(Alliance::class, 'alliance_id');
    }

    public function attackQueues()
    {
        return $this->hasMany(AttackQueue::class, 'player_id');
    }

    public function buildQueues()
    {
        return $this->hasMany(BuildQueue::class, 'player_id');
    }

    public function trainQueues()
    {
        return $this->hasMany(TrainQueue::class, 'player_id');
    }

    public function exploreQueues()
    {
        return $this->hasMany(ExploreQueue::class, 'player_id');
    }

    public function sentMessages()
    {
        return $this->hasMany(PlayerMessage::class, 'from_player_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(PlayerMessage::class, 'to_player_id');
    }

    public function loginEntries()
    {
        return $this->hasMany(LoginEntry::class, 'player_id');
    }

    public function blockedPlayers()
    {
        return $this->hasMany(BlockMessage::class, 'player_id');
    }

    public function autoLocalTrades()
    {
        return $this->hasMany(AutoLocalTrade::class, 'player_id');
    }

    public function getTotalLandAttribute(): int
    {
        if (array_key_exists('total_land', $this->attributes)) {
            return (int) $this->attributes['total_land'];
        }
        return ($this->mland ?? 0) + ($this->fland ?? 0) + ($this->pland ?? 0);
    }

    public function getTotalArmyAttribute(): int
    {
        return $this->swordsman + $this->archers + $this->horseman
            + $this->catapults + $this->macemen + $this->trained_peasants
            + $this->thieves + $this->uunit;
    }

    public function isAlive(): bool
    {
        return $this->killed_by === 0;
    }

    public function isAdmin(): bool
    {
        return $this->is_admin === 1;
    }
}
