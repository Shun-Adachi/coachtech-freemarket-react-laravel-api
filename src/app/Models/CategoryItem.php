<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryItem extends Model
{
    use HasFactory;

    // テーブル名参照のカスタマイズ
    protected $table = 'category_item';

    protected $fillable = [
        'item_id',
        'category_id',
    ];

    // ItemとUserのリレーション
    public function Category()
    {
        return $this->belongsTo(Category::class);
    }
}
