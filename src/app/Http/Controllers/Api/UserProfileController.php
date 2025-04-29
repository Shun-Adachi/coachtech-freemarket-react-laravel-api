<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\UserRequest;
use App\Models\Trade;
use App\Models\Item;
use App\Models\Purchase;

class UserProfileController extends Controller
{

    /**
     * マイページ用データを返す
     * GET /api/user?tab={tab}
     */
    public function index(Request $request)
    {
        $user     = $request->user();
        $userId   = $user->id;
        $tab      = $request->query('tab');

        //
        // 1) 「評価未入力の取引」を集める
        //
        $trades = Trade::with(['purchase.item', 'tradeMessages'])
            ->where(function($query) use ($userId) {
                $query->where(function ($q) use ($userId) {
                    $q->whereHas('purchase', fn($q2) =>
                            $q2->where('user_id', $userId)
                        )
                      ->whereNull('buyer_rating_points');
                })->orWhere(function ($q) use ($userId) {
                    $q->whereHas('purchase.item', fn($q2) =>
                            $q2->where('user_id', $userId)
                        )
                      ->whereNull('seller_rating_points');
                });
            })
            ->get();

        //
        // 2) 各取引に最新メッセージ日時を付与
        //
        $trades->each(function ($trade) {
            $latest = $trade->tradeMessages->max('created_at');
            $trade->latest_trade_message_at = $latest ?? $trade->created_at;
        });

        //
        // 3) 商品ごとにグループ化 → アイテムコレクションを作成
        //
        $grouped = $trades->groupBy(fn($t) => $t->purchase->item->id);
        $items = $grouped->map(function ($tradesForItem) use ($userId) {
            $firstTrade = $tradesForItem->first();
            $item       = $firstTrade->purchase->item;
            $item->trade_id = $firstTrade->id;

            // 相手のメッセージだけ抽出
            $partnerMsgs = $tradesForItem
                ->flatMap(fn($t) => $t->tradeMessages->where('user_id', '!=', $userId));

            $item->latest_partner_message_at =
                $partnerMsgs->max('created_at');
            $item->message_count =
                $partnerMsgs->where('is_read', false)->count();

            return $item;
        });

        // 未読メッセージ合計
        $totalTradePartnerMessages = $items->sum('message_count');

        // 新着順にソート
        $items = $items
            ->sortByDesc(fn($i) =>
                $i->latest_partner_message_at
                    ? $i->latest_partner_message_at->timestamp
                    : 0
            )
            ->values();

        //
        // 4) タブによる切替（sell / purchase / default）
        //
        if ($tab === 'sell') {
            $items = Item::where('user_id', $userId)->get();
        } elseif (! $tab) {
            $purchaseItemIds = Purchase::where('user_id', $userId)
                                      ->pluck('item_id');
            $items = Item::whereIn('id', $purchaseItemIds)->get();
        }

        //
        // 5) 自分に関わる全取引から平均評価を算出
        //
        $allTrades = Trade::with(['purchase.item'])
            ->where(fn($q) => $q
                ->whereHas('purchase', fn($q2) => $q2->where('user_id', $userId))
                ->orWhereHas('purchase.item', fn($q2) => $q2->where('user_id', $userId))
            )->get();

        $totalRating = 0;
        $ratingCount = 0;
        foreach ($allTrades as $trade) {
            // 購入者なら seller_rating_points
            if ($trade->purchase->user_id === $userId && $trade->seller_rating_points !== null) {
                $totalRating += $trade->seller_rating_points;
                $ratingCount++;
            }
            // 出品者なら buyer_rating_points
            if ($trade->purchase->item->user_id === $userId && $trade->buyer_rating_points !== null) {
                $totalRating += $trade->buyer_rating_points;
                $ratingCount++;
            }
        }
        $averageTradeRating = $ratingCount > 0
            ? round($totalRating / $ratingCount)
            : 0;

        //
        // 6) JSON で返却
        //
        return response()->json([
            'user'                       => $user,
            'items'                      => $items,
            'totalTradePartnerMessages'  => $totalTradePartnerMessages,
            'averageTradeRating'         => $averageTradeRating,
            'ratingCount'                => $ratingCount,
            'tab'                        => $tab,
        ]);
    }

    /**
     * 認証済ユーザーの情報を返す
     * GET  /api/user
     */
    public function show(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * ユーザー情報（プロフィール）を更新
     * PUT|PATCH  /api/user
     */
    public function update(UserRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        // サムネイル画像アップロードの処理
        if ($request->hasFile('thumbnail')) {
            // 旧ファイルがあれば削除
            if ($user->thumbnail && Storage::disk('public')->exists($user->thumbnail)) {
                Storage::disk('public')->delete($user->thumbnail);
            }
            // 新しいファイルを保存（public/users ディレクトリ）
            $path = $request->file('thumbnail')->store('users', 'public');
            $data['thumbnail'] = $path;
        }

        $user->update($data);

        return response()->json($user);
    }

    /**
     * サムネイル画像のみ削除
     * DELETE  /api/user/thumbnail
     */
    public function deleteThumbnail(Request $request)
    {
        $user = $request->user();

        if ($user->thumbnail && Storage::disk('public')->exists($user->thumbnail)) {
            Storage::disk('public')->delete($user->thumbnail);
            // DB 側もクリア
            $user->thumbnail = null;
            $user->save();
        }

        return response()->json(['message' => 'Thumbnail deleted.']);
    }
}
