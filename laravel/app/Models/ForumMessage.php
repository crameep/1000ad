<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForumMessage extends Model
{
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
