<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    //ItemとCategoryのリレーション
    public function Item()
    {
        return $this->belongsTo(Item::class);
    }
}
