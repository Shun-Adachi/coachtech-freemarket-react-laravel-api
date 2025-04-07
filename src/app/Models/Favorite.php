<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'item_id',
    ];

    // FaivoriteとItemのリレーション
    public function Item()
    {
        return $this->belongsTo(Item::class);
    }

    // FaivoriteとUserのリレーション
    public function User()
    {
        return $this->belongsTo(User::class);
    }
}
