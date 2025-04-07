<?php

namespace App\Http\Controllers;

use App\Mail\TradeCompletedMail;
use App\Models\Trade;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class TradeController extends Controller
{
    public function complete(Trade $trade)
    {
        if ($trade->purchase->user->id !== Auth::id()) {
            abort(403, 'この取引を完了する権限がありません。');
        }

        $trade->is_complete = true;
        $trade->save();

        $tradePartner = $trade->purchase->item->user;
        Mail::to($tradePartner->email)->send(new TradeCompletedMail($trade));

        return redirect()->back()->with('message', '取引が完了しました。');
    }
}
