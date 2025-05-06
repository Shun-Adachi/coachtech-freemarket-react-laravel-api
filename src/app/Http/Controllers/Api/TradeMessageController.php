<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TradeMessageRequest;
use App\Http\Requests\TradeMessageUpdateRequest;
use App\Models\Trade;
use App\Models\TradeMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TradeMessageController extends Controller
{
    /**
     * メッセージ一覧取得 API
     * GET /api/trades/{trade}/messages
     */
    public function index(Trade $trade, Request $request): JsonResponse
    {
        // 1) 現在のユーザー
        $user   = $request->user();
        $userId = $user->id;

        // 2) Trade に関連する purchase, item, 出品者・購入者をロード
        $trade->load('purchase.item.user', 'purchase.user');
        $purchase    = $trade->purchase;
        $item        = $purchase->item;
        // 価格はフォーマット
        $item->price = number_format($item->price);

        // 取引相手を決定
        if ($purchase->user->id === $userId) {
            // 自分が購入者なら相手は item.user
            $tradePartner = $item->user;
        } else {
            // 自分が出品者なら相手は purchase.user
            $tradePartner = $purchase->user;
        }

        // 3) 相手の未読メッセージを既読に
        TradeMessage::where('trade_id', $trade->id)
            ->where('user_id', '!=', $userId)
            ->update(['is_read' => true]);

        // 4) この取引のメッセージ一覧（古い順）
        $messages = TradeMessage::with('user')
            ->where('trade_id', $trade->id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($msg) => [
                'id'         => $msg->id,
                'message'    => $msg->message,
                'image_url'  => $msg->image_path
                                 ? asset('storage/' . $msg->image_path)
                                 : null,
                'user'       => [
                    'id'            => $msg->user->id,
                    'name'          => $msg->user->name,
                    'thumbnail_url' => $msg->user->thumbnail_path
                        ? asset('storage/' . $msg->user->thumbnail_path)
                        : null,
                ],
                'is_read'    => $msg->is_read,
                'created_at' => $msg->created_at->toDateTimeString(),
            ]);

        // 5) サイドバー用：評価未入力の取引一覧
        $sidebarTrades = Trade::with(['purchase.item', 'tradeMessages'])
            ->where('id', '!=', $trade->id)
            ->where(function ($q) use ($userId) {
                $q->where(function ($q2) use ($userId) {
                    // 自分が購入者の場合、buyer_rating_points が null
                    $q2->whereHas('purchase', fn($q3) =>
                        $q3->where('user_id', $userId)
                    )->whereNull('buyer_rating_points');
                })->orWhere(function ($q2) use ($userId) {
                    // 自分が出品者の場合、seller_rating_points が null
                    $q2->whereHas('purchase.item', fn($q3) =>
                        $q3->where('user_id', $userId)
                    )->whereNull('seller_rating_points');
                });
            })
            ->get()
            ->map(fn($t) => [
                'id'       => $t->id,
                'itemName' => $t->purchase->item->name,
            ]);

        // 6) 編集対象メッセージ ID（セッションから）
        $editingMessageId = session('editingMessageId');

        // 7) モーダル表示フラグ（取引未完了なら true）
        $showModal = ! $trade->is_complete;

        return response()->json([
            'sidebarTrades'   => $sidebarTrades,
            'trade'           => [
                'id'               => $trade->id,
                'is_complete'      => $trade->is_complete,
                'purchase_user_id' => $purchase->user->id,
            ],
            'item'            => [
                'id'        => $item->id,
                'name'      => $item->name,
                'price'     => $item->price,
                'image_url' => asset('storage/' . $item->image_path),
            ],
            'tradePartner'    => [
                'id'            => $tradePartner->id,
                'name'          => $tradePartner->name,
                'thumbnail_url' => $tradePartner->thumbnail_path
                                     ? asset('storage/' . $tradePartner->thumbnail_path)
                                     : null,
            ],
            'messages'        => $messages,
            'editingMessageId'=> $editingMessageId,
            'showModal'       => $showModal,
            'currentUserId'   => $userId,
        ], 200);
    }


    /**
     * メッセージ送信 API
     * POST /api/trades/{trade}/messages
     */
    public function store(TradeMessageRequest $request,Trade $trade): JsonResponse {
        $user = $request->user();

        // 画像があれば保存
        $path = null;
        if ($file = $request->file('image')) {
            $path = $file->store('images/trade_messages', 'public');
        }

        $msg = TradeMessage::create([
            'trade_id'   => $trade->id,
            'user_id'    => $user->id,
            'message'    => $request->message,
            'image_path' => $path,
            'is_read'    => false,
        ]);

        return response()->json([
            'message' => [
                'id'         => $msg->id,
                'message'    => $msg->message,
                'image_url'  => $path ? asset('storage/' . $path) : null,
                'user'       => [
                    'id'            => $user->id,
                    'name'          => $user->name,
                    'thumbnail_url' => $user->thumbnail_path
                        ? asset('storage/' . $user->thumbnail_path)
                        : null,
                ],
                'is_read'    => $msg->is_read,
                'created_at' => $msg->created_at->toDateTimeString(),
            ]
        ], 201);
    }

    /**
     * メッセージ更新 API
     * PUT /api/trades/{trade}/messages/{message}
     */
    public function update(
        TradeMessageUpdateRequest $request,Trade $trade,TradeMessage $message): JsonResponse {
        // 権限チェック（自分のメッセージのみ）
        if ($message->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'このメッセージを更新する権限がありません。'
            ], 403);
        }

        $message->message = $request->updateMessage;
        $message->save();

        return response()->json([
            'message' => [
                'id'         => $message->id,
                'message'    => $message->message,
                'image_url'  => $message->image_path
                    ? asset('storage/' . $message->image_path)
                    : null,
                'is_read'    => $message->is_read,
                'created_at' => $message->created_at->toDateTimeString(),
            ]
        ], 200);
    }

    /**
     * メッセージ削除 API
     * DELETE /api/trades/{trade}/messages/{message}
     */
    public function destroy(Trade $trade,TradeMessage $message): JsonResponse {
        // 権限チェック（自分のメッセージのみ）
        if ($message->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'このメッセージを削除する権限がありません。'
            ], 403);
        }

        // 画像があれば削除
        if ($message->image_path) {
            Storage::disk('public')->delete($message->image_path);
        }

        $message->delete();

        return response()->json([
            'message' => 'メッセージを削除しました。'
        ], 200);
    }
}
