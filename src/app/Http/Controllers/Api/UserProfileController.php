<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\UserRequest;
use App\Models\Trade;
use App\Models\Item;
use App\Models\Purchase;
use Illuminate\Support\Str;

class UserProfileController extends Controller
{

    /**
     * ユーザープロフィール情報を返却
     * - 出品中の商品
     * - 購入済みの商品
     * - 取引中の商品 (未完了且つ新着メッセージ順)
     * - 未読メッセージ数合計
     * - 評価平均値
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        // 出品中（sellingItems）: 購入済みかどうかに関係なく、自身が出品したすべての商品を取得
        $sellingItems = Item::where('user_id', $user->id)
            ->get()
            ->map(fn($i) => [
                'id'            => $i->id,
                'trade_id'      => null,
                'name'          => $i->name,
                'image_url'     => asset('storage/' . $i->image_path),
                // 購入履歴が存在すれば is_sold = true
                'is_sold'       => Purchase::where('item_id', $i->id)->exists(),
                'message_count' => 0,
            ]);

        // 購入済み（purchasedItems）：自身が購入した全商品を取得（メッセージ不要）
        $purchasedItems = Purchase::where('user_id', $user->id)
            ->with(['item', 'trade'])
            ->get()
            ->map(fn($p) => [
                'id'            => $p->item->id,
                'trade_id'      => $p->trade ? $p->trade->id : null,
                'name'          => $p->item->name,
                'image_url'     => asset('storage/' . $p->item->image_path),
                'is_sold'       => true,
                'message_count' => 0,
            ]);

        // 取引中（tradingItems）：未完了かつ当事者、メッセージの最新日時でソート
        $tradeBaseQuery = Trade::where('is_complete', false)
            ->whereHas('purchase', fn($q) => $q->where('user_id', $user->id)
                ->orWhereHas('item', fn($q2) => $q2->where('user_id', $user->id)));

        $tradingItems = $tradeBaseQuery
            ->with(['purchase.item', 'tradeMessages'])
            ->get()
            ->sortByDesc(fn(Trade $t) => $t->tradeMessages->max('created_at'))
            ->values()
            ->map(fn($t) => [
                'id'            => $t->purchase->item->id,
                'trade_id'      => $t->id,
                'name'          => $t->purchase->item->name,
                'image_url'     => asset('storage/' . $t->purchase->item->image_path),
                'is_sold'       => true,
                'message_count' => $t->tradeMessages
                    ->where('is_read', false)
                    ->where('user_id', '!=', $user->id)
                    ->count(),
            ]);

        // 未読メッセージ数合計
        $totalUnread = $tradeBaseQuery
            ->withCount([
                'tradeMessages as unread_count' => fn($q) => $q
                    ->where('is_read', false)
                    ->where('user_id', '!=', $user->id)
            ])
            ->get()
            ->sum('unread_count');

        // 評価平均値と件数
        // (1) 自分が【出品者】だった取引に対する buyer_rating_points を集計
        $sellerQuery = Trade::whereHas(
            'purchase.item',
            fn($q) =>
            $q->where('user_id', $user->id)
        )
            ->whereNotNull('buyer_rating_points');

        $sellerCount = $sellerQuery->count();
        $sellerSum   = $sellerQuery->sum('buyer_rating_points');

        // (2) 自分が【購入者】だった取引に対する seller_rating_points を集計
        $buyerQuery = Trade::whereHas(
            'purchase',
            fn($q) =>
            $q->where('user_id', $user->id)
        )
            ->whereNotNull('seller_rating_points');

        $buyerCount = $buyerQuery->count();
        $buyerSum   = $buyerQuery->sum('seller_rating_points');

        // (3) 合計件数と合計得点から平均を算出
        $totalCount     = $sellerCount + $buyerCount;
        $averageRating  = $totalCount > 0
            ? ($sellerSum + $buyerSum) / $totalCount
            : 0;

        // ★レスポンスに返す評価件数は、上記合計件数をそのまま使う
        $ratingCount    = $totalCount;

        return response()->json([
            'user' => [
                'id'            => $user->id,
                'name'          => $user->name,
                'thumbnail_url' => $user->thumbnail_path,
            ],
            'sellingItems'              => $sellingItems,
            'purchasedItems'            => $purchasedItems,
            'tradingItems'              => $tradingItems,
            'totalTradePartnerMessages' => $totalUnread,
            'averageTradeRating'        => round($averageRating, 1),
            'ratingCount'               => $ratingCount,
        ], 200);
    }

    /**
     * 認証済ユーザーの情報を返す
     * GET  /api/mypage/profile'
     */
    public function show(Request $request)
    {
        $user = auth()->user();

        // ここで必要な項目だけ抜き出して JSON で返していると仮定
        return response()->json([
            'id'                  => $user->id,
            'name'                => $user->name,
            'thumbnail_url'       => $user->thumbnail_path ? asset('storage/' . $user->thumbnail_path) : null,
            'current_post_code'   => $user->current_post_code,
            'current_address'     => $user->current_address,
            'current_building'    => $user->current_building,
        ]);
    }

    /**
     * ユーザー情報（プロフィール）を更新
     * PATCH  /api/mypage/profile
     */
    public function update(UserRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        // サムネイル画像アップロードの処理
        if ($request->hasFile('image')) {
            // default/users/以外の旧ファイルを削除
            if (!Str::startsWith($user->thumbnail_path, 'default/users/')) {
                Storage::disk('public')->delete($user->thumbnail_path);
            }
            // 新しいファイルを保存（public/users ディレクトリ）
            $path = $request->file('image')->store('users', 'public');
            $data['thumbnail_path'] = $path;
        }

        unset($data['image']);
        // バリデート済みデータで更新（thumbnail_path は上書き済み）
        $user->fill($data);
        $user->save();

        // フロントで使いやすいように URL を付与
        $user->thumbnail_url = $user->thumbnail_path
            ? Storage::url($user->thumbnail_path)
            : null;

        return response()->json([
            'user' => $user
        ], 200);
    }
}
