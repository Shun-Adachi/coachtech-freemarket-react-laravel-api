<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /**
     * この商品についた「お気に入り」一覧
     */
    public function favorites()
    {
        // items.id = favorites.item_id
        return $this->hasMany(Favorite::class);
    }

    /**
     * この商品に紐づく購入履歴
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(\App\Models\Purchase::class);
    }

    //キーワード検索
    public function scopeKeywordSearch($query, $keyword)
    {
        if (!empty($keyword)) {
            $query->where('name', 'like', '%' . $keyword . '%');
        }
    }
}
