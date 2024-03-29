<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\ModelStatus\HasStatuses;
use App\Models\Constant;

class AcceptedChallenge extends Model
{
    use HasStatuses;
    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime:'.Constant::DATE_FORMAT,
        'updated_at' => 'datetime:'.Constant::DATE_FORMAT,
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->select(['id','name','username','balance','avatar']);
    }

    public function challenge()
    {
        return $this->belongsTo(Challenge::class)->withTrashed()->select(['id','title','description','start_time','file','result_type','user_id','duration_days', 'duration_hours', 'duration_minutes', 'allowVoter']);
    }

    // function amounts()
    // {
    //     return $this->hasManyThrough(Amount::class, Challenge::class, 'id', 'challenge_id');
    // }

    public function getStatusAttribute()
    {
        return $this->status()->name;
    }

    public function submitChallenge()
    {
        return $this->hasOne(SubmitChallenge::class);
    }

    public function submitFiles()
    {
        return $this->hasMany(SubmitFile::class);
    }


}
