<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Constant;

class Reaction extends Model
{
    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime:'.Constant::DATE_FORMAT,
        'updated_at' => 'datetime:'.Constant::DATE_FORMAT,
        'like' => 'boolean',
        'unlike' => 'boolean',
        'favorite' => 'boolean',
    ];

    protected $hidden = [
        'created_at', 'updated_at'
    ];

    public function challenge()
    {
        return $this->belongsTo(Challenge::class, 'reactionable_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    function amounts()
    {
        return $this->hasManyThrough(Amount::class, Challenge::class, 'id', 'challenge_id');
    }

    public function reactionable()
    {
        return $this->morphTo();
    }
}
