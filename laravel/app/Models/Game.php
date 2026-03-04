<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Game Model
 *
 * Represents a game instance with its own configuration.
 * Multiple games can run concurrently with different settings.
 */
class Game extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'deathmatch_start' => 'datetime',
        'deathmatch_mode' => 'boolean',
        'is_admin' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Get a value from the settings JSON, with optional default.
     */
    public function setting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Set a value in the settings JSON.
     */
    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;
    }

    /**
     * Check if the game has ended.
     */
    public function hasEnded(): bool
    {
        if ($this->status === 'ended') {
            return true;
        }
        if ($this->end_date && $this->end_date->isPast()) {
            return true;
        }
        return false;
    }

    /**
     * Check if the game is currently active and playable.
     */
    public function isPlayable(): bool
    {
        return $this->status === 'active' && !$this->hasEnded();
    }

    // Relations

    public function players()
    {
        return $this->hasMany(Player::class, 'game_id');
    }

    public function alliances()
    {
        return $this->hasMany(Alliance::class, 'game_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function prizePayouts()
    {
        return $this->hasMany(PrizePayout::class);
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePlayable($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>', now());
            });
    }
}
