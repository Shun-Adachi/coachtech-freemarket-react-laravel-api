<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\Favorite;
use App\Models\Comment;
use App\Models\CategoryItem;
use App\Models\Purchase;
use App\Models\User;
use App\Http\Requests\CommentRequest;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Log;

class ItemController extends BaseController
{
    /**
     * 商品一覧を取得（おすすめ／マイリスト対応）
     * GET /api/items
     */
    public function index(Request $request)
    {
        // ─── ① 任意認証（Bearer トークンがあればユーザ解決） ───
        $loginId = null;

        if ($token = $request->bearerToken()) {
            $pat = PersonalAccessToken::findToken($token);
            // tokenable は User モデル
            if ($pat && $pat->tokenable_type === User::class) {
                $loginId = $pat->tokenable_id;
            }
        }

        // ── 1) すべての公開アイテムを取得 ────────────────
        //    必要な関連だけ preload しておく
        $items = Item::query()
            ->with(['favorites' => function ($q) use ($loginId) {
                // ログイン中ユーザがお気に入り済みか判定するための関連
                if ($loginId) {
                    $q->where('user_id', $loginId);
                }
            }])
            ->orderByDesc('created_at')
            ->get();


        $soldItemIds = Purchase::pluck('item_id')->toArray();

        // ── 2) レスポンス用の配列整形 ─────────────────
        $payload = $items->map(function ($item) use ($loginId, $soldItemIds) {
            return [
                'id'             => $item->id,
                'name'           => $item->name,
                'image_url'      => asset('storage/' . $item->image_path),
                'is_sold'     => in_array($item->id, $soldItemIds, true),
                'isFavorite' => $loginId ? $item->favorites->isNotEmpty() : false,
            ];
        });

        return response()->json($payload);
    }

    // 商品詳細ページ表示
    public function show(Request $request, $itemId)
    {
        // ユーザー情報取得
        $user = $request->user();
        $userId = $user->id ?? null;

        // 商品詳細取得
        $item = Item::with(['user', 'condition'])->where('id', $itemId)->first();
        $purchase =  Purchase::where('item_id', $itemId)->exists();

        // お気に入り情報取得
        if ($userId) {
            $isFavorite = Favorite::where('item_id', $itemId)->where('user_id', $userId)->exists();
        } else {
            $isFavorite = false;
        }

        $favoritesCount = Favorite::where('item_id', $itemId)->count();

        // コメント数取得
        $comments = Comment::with('user')->where('item_id', $itemId)->get();
        $commentsCount = $comments->count();

        // 関連カテゴリー取得
        $itemCategories = CategoryItem::with('category')->where('item_id', $itemId)->get();

        return response()->json([
            'id'             => $item->id,
            'name'           => $item->name,
            'image_url'      => asset('storage/' . $item->image_path),
            'price'          => number_format($item->price),
            'description'    => $item->description,
            'user'           => [
                'id'   => $item->user->id,
                'name' => $item->user->name,
            ],
            'purchase'       => $purchase,
            'isFavorite'     => (bool) $isFavorite,
            'favoritesCount' => $favoritesCount,
            'commentsCount'  => $commentsCount,
            'categories'     => $itemCategories->map(fn($ic) => $ic->category->name),
            'condition'      => [
                'id'   => $item->condition->id,
                'name' => $item->condition->name,
            ],
        ], 200);
    }

    /**
     * お気に入り登録
     */
    public function favorite(Request $request, $itemId)
    {
        $user = $request->user();
        $item = Item::findOrFail($itemId);

        // 自分の出品には登録不可
        if ($item->user_id === $user->id) {
            return response()->json(['message' => '自身の出品はお気に入り登録できません'], Response::HTTP_FORBIDDEN);
        }

        Favorite::firstOrCreate([
            'item_id' => $itemId,
            'user_id' => $user->id,
        ]);

        return response()->json(['message' => 'お気に入りに登録しました'], Response::HTTP_OK);
    }

    /**
     * お気に入り解除
     */
    public function unfavorite(Request $request, $itemId)
    {
        $user = $request->user();

        Favorite::where('item_id', $itemId)
            ->where('user_id', $user->id)
            ->delete();

        return response()->json(['message' => 'お気に入りを解除しました'], Response::HTTP_OK);
    }

    /**
     * 指定アイテムのコメント一覧取得
     */
    public function comments(Request $request, $itemId)
    {
        $comments = Comment::with('user')
            ->where('item_id', $itemId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($c) => [
                'id'        => $c->id,
                'message'   => $c->comment,
                'user'      => [
                    'name'          => $c->user->name,
                    'thumbnail_url' => $c->user->thumbnail_path
                        ? asset('storage/' . $c->user->thumbnail_path)
                        : null,
                ],
                'createdAt' => $c->created_at->toDateTimeString(),
            ]);

        return response()->json(['comments' => $comments], 200);
    }

    /**
     * コメント追加
     */
    public function addComment(CommentRequest $request, $itemId)
    {
        $user = $request->user();
        $c = Comment::create([
            'comment' => $request->comment,
            'user_id' => $user->id,
            'item_id' => $itemId,
        ]);

        // 返却用に整形
        $new = [
          'id'        => $c->id,
          'message'   => $c->comment,
          'user'      => [
             'name'          => $user->name,
             'thumbnail_url' => $user->thumbnail_path
                 ? asset('storage/' . $user->thumbnail_path)
                 : null,
          ],
          'createdAt' => $c->created_at->toDateTimeString(),
        ];

        return response()->json(['comment' => $new], 201);
    }

}
