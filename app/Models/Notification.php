<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'challenge_id', 'user_id', 'title', 'body'
    ];

    protected $hidden = [
        'user_id'
    ];

    protected $with = [
        'challenge'
    ];

    public function getCreatedAtAttribute($value)
    {
        return time_elapsed_string($value);
    }

    public function challenge()
    {
        return $this->belongsTo(Challenge::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}