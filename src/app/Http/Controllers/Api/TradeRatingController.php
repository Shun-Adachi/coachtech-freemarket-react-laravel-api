<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trade;
use Illuminate\Http\Request;

class TradeRatingController extends Controller
{
    /**
     * 取引評価登録 API
     * POST /api/trades/{trade}/rate
     */
    public function store(Request $request, Trade $trade)
    {
        // バリデーション
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
        ]);

        // 購入者か出品者か判定し、対応するポイント列にセット
        $isBuyer = ($trade->purchase->user_id === $request->user()->id);
        if ($isBuyer) {
            $trade->buyer_rating_points = $request->rating;
        } else {
            $trade->seller_rating_points = $request->rating;
        }

        $trade->save();

        // JSON で成功メッセージを返却
        return response()->json([
            'message' => '取引評価を送信しました。'
        ], 200);
    }
}
