<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Constant;

class AcceptedChallenge extends Model
{
    protected $casts = [
        'created_at' => 'datetime:'.Constant::DATE_FORMAT,
        'updated_at' => 'datetime:'.Constant::DATE_FORMAT,
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->select(['id','name','username']);
    }

    public function challenge()
    {
        return $this->belongsTo(Challenge::class);
    }
}
