<?php

namespace App\Models;

use App\Models\Traits\BelongsToGame;
use Illuminate\Database\Eloquent\Model;

class ForumMessage extends Model
{
    use BelongsToGame;
    protected $guarded = ['id'];

    public $incrementing = false;

    protected $casts = [
        'last_update' => 'datetime',
    ];

    public $timestamps = false;

    public function parent()
    {
        return $this->belongsTo(ForumMessage::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(ForumMessage::class, 'parent_id');
    }
}
