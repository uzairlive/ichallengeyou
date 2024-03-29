<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Constant;

class Category extends Model
{

    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime:'.Constant::DATE_FORMAT,
        'updated_at' => 'datetime:'.Constant::DATE_FORMAT,
    ];

    protected $hidden = [
        'category_id', 'created_at', 'updated_at'
    ];

    public function parentCategory()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function childCategories()
    {
        return $this->hasMany(Category::class, 'category_id', 'id');
    }

    public function challenges()
    {
        return $this->hasMany(Challenge::class)->withTrashed();
    }
}
