<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'price',
        'description',
        'user_id',
        'condition_id',
        'image_path',
    ];

    //ItemとConditionのリレーション
    public function condition()
    {
        return $this->belongsTo(Condition::class);
    }

    //ItemとUserのリレーション
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    //ItemとCategoryのリレーション
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    //キーワード検索
    public function scopeKeywordSearch($query, $keyword)
    {
        if (!empty($keyword)) {
            $query->where('name', 'like', '%' . $keyword . '%');
        }
    }
}
