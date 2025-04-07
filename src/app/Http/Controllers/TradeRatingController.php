<?php

namespace App\Http\Controllers;
use App\Models\Trade;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TradeRatingController extends Controller
{
    public function store(Request $request, Trade $trade): JsonResponse
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
        ]);

        $isBuyer = ($trade->purchase->user_id === auth()->id());
        if ($isBuyer) {
            $trade->buyer_rating_points = $request->rating;
        } else {
            $trade->seller_rating_points = $request->rating;
        }

        $trade->save();

        return response()->json([
            'message' => '取引評価を送信しました。',
            'trade' => $trade
        ]);
    }
}
