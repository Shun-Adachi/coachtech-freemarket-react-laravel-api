<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'item_id',
        'payment_method_id',
        'shipping_post_code',
        'shipping_address',
        'shipping_building',
    ];

   //PurchaseとUserのリレーション
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    //PurchaseとTradeのリレーション
    public function trade()
    {
        return $this->hasOne(Trade::class, 'purchase_id');
    }

    //PurchaseとItemのリレーション
    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
