<?php

namespace App\Http\Controllers;

use App\Mail\TradeCompletedMail;
use App\Models\Trade;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\JsonResponse;

class TradeController extends Controller
{
    public function complete(Trade $trade): JsonResponse
    {
        if ($trade->purchase->user->id !== Auth::id()) {
            return response()->json(['error' => 'この取引を完了する権限がありません。'], 403);
        }

        $trade->is_complete = true;
        $trade->save();

        $tradePartner = $trade->purchase->item->user;
        Mail::to($tradePartner->email)->send(new TradeCompletedMail($trade));

        return response()->json(['message' => '取引が完了しました。']);
    }
}
