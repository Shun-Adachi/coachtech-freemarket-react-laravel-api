<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trade;
use App\Mail\TradeCompletedMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;


class TradeController extends Controller
{
    /**
     * 取引完了 API
     * POST /api/trades/{trade}/complete
     */
    public function complete(Trade $trade, Request $request): JsonResponse
    {
        $user = $request->user();

        // 購入者のみが「取引完了」を呼び出せる
        if ($trade->purchase->user->id !== $user->id) {
            return response()->json([
                'message' => 'この取引を完了する権限がありません。'
            ], 403);
        }

        // 完了フラグを立てる
        $trade->is_complete = true;
        $trade->save();

        // 取引相手へ完了メールを送信
        $tradePartner = $trade->purchase->item->user;
        Mail::to($tradePartner->email)
            ->send(new TradeCompletedMail($trade));

        // JSON レスポンスで返す
        return response()->json([
            'message' => '取引が完了しました。'
        ], 200);
    }
}
