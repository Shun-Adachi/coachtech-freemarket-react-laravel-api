<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeMessage extends Model
{
    use HasFactory;
    protected $fillable = [
        'trade_id',
        'user_id',
        'message',
        'image_path',
        'is_read',
    ];

    //TradeMessageとTradeのリレーション
    public function trade()
    {
        return $this->belongsTo(Trade::class);
    }

    //TradeMessageとUserのリレーション
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
