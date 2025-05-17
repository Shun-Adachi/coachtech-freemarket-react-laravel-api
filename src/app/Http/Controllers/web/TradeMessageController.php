<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\TradeMessageRequest;
use App\Http\Requests\TradeMessageUpdateRequest;
use App\Models\Trade;
use App\Models\TradeMessage;

class TradeMessageController extends Controller
{
    // 取引メッセージページ表示
    public function index(Trade $trade, Request $request)
    {
        // 現在のユーザー取得
        $user = Auth::user();
        $userId = $user->id;

        // 自身に関連する取引および商品情報取得
        $trade->load('purchase.item.user', 'purchase.user');
        $item = $trade->purchase->item;
        if ($trade->purchase->user->id == $userId) {
            $tradePartner = $trade->purchase->item->user;
        } else {
            $tradePartner = $trade->purchase->user;
        }
        $item->price = number_format($item->price);

        // 相手のメッセージを全て既読に更新
        TradeMessage::where('trade_id', $trade->id)
            ->where('user_id', '!=', $userId)
            ->update(['is_read' => true]);

        // 現在の取引に紐づくメッセージを、古い順に取得
        $messages = TradeMessage::with('user')
            ->where('trade_id', $trade->id)
            ->orderBy('created_at', 'asc')
            ->get();

        // 編集対象のメッセージを取得
        $editingMessageId = session('editingMessageId');

        // 取引が自分に関係しているもので評価を入力していないものを表示
        $sidebarTrades = Trade::with(['purchase.item', 'tradeMessages'])
            ->where('id', '!=',  $trade->id)
            ->where(function($query) use ($userId) {
                $query->where(function ($q) use ($userId) {
                    // 自分が購入者の場合、buyer_rating_pointsがnull
                    $q->whereHas('purchase', function ($q2) use ($userId) {
                        $q2->where('user_id', $userId);
                    })->whereNull('buyer_rating_points');
                })->orWhere(function ($q) use ($userId) {
                    // 自分が出品者の場合、seller_rating_pointsがnull
                    $q->whereHas('purchase.item', function ($q2) use ($userId) {
                        $q2->where('user_id', $userId);
                    })->whereNull('seller_rating_points');
                });
            })
            ->get();

        return view('trade-chat', compact('trade','messages','sidebarTrades','item','tradePartner', 'editingMessageId'));
    }

    // メッセージ保存処理
    public function store(TradeMessageRequest $request, Trade $trade)
    {
        $tradeMessage = new TradeMessage();
        $tradeMessage->user_id  = Auth::id();   // 送信者のユーザーID
        $tradeMessage->trade_id = $trade->id;   // 該当の取引ID
        $tradeMessage->message  = $request->message;

        // 画像がアップロードされている場合の処理
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('messages', 'public');
            $tradeMessage->image_path = $path;
        }

        $tradeMessage->save();
        return redirect('/trades' . '/' . $trade->id . '/messages')->with('message', 'メッセージを送信しました。');
    }

    // 編集モード表示
    public function edit(Trade $trade, TradeMessage $message)
    {
        session()->flash('editingMessageId', $message->id);
        return redirect('/trades' . '/' . $trade->id . '/messages');
    }

    // 既存のメッセージ更新処理
    public function update(TradeMessageUpdateRequest $request, Trade $trade, TradeMessage $message)
    {
        $message->message = $request->updateMessage;
        $message->save();
        return redirect('/trades' . '/' . $trade->id . '/messages')->with('message', 'メッセージが更新されました。');
    }

    // 削除処理
    public function destroy(Trade $trade, TradeMessage $message)
    {
        $message->delete();
        return redirect('/trades' . '/' . $trade->id . '/messages')->with('message', 'メッセージが削除されました。');
    }
}
