<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrizePayout extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function getAmountAttribute(): float
    {
        return $this->amount_cents / 100;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
