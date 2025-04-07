<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Item;
use App\Models\Favorite;
use App\Models\Comment;
use App\Models\CategoryItem;
use App\Models\Purchase;
use App\Http\Requests\CommentRequest;
use Illuminate\Http\JsonResponse;

class ItemController extends Controller
{
    // 商品一覧取得
    public function index(Request $request): JsonResponse
    {
        // ユーザー情報・タブ情報・検索取得
        $user = Auth::user();
        $userId = $user->id ?? null;
        $tab = $request->tab;
        $keyword = $request->input('keyword', '');

        // マイリスト
        if ($tab === 'mylist') {
            $favoriteItemIds = Favorite::where('user_id', $userId)->pluck('item_id');
            $items = Item::whereIn('id', $favoriteItemIds)->KeywordSearch($keyword)->get();
        }
        // 商品一覧
        else {
            $items = Item::where('user_id', '!=', $userId)->KeywordSearch($keyword)->get();
        }

        $soldItemIds = Purchase::pluck('item_id')->toArray();

        return response()->json([
            'items' => $items,
            'soldItemIds' => $soldItemIds
        ]);
    }

    // 商品詳細取得
    public function show(Request $request, $itemId): JsonResponse
    {
        // ユーザー情報取得
        $user = Auth::user();
        $userId = $user->id ?? null;

        // 商品詳細取得
        $item = Item::with(['user', 'condition'])->where('id', $itemId)->first();
        $purchase = Purchase::where('item_id', $itemId)->exists();

        $isFavorite = false;
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
            'item' => $item,
            'purchase' => $purchase,
            'isFavorite' => $isFavorite,
            'favoritesCount' => $favoritesCount,
            'comments' => $comments,
            'commentsCount' => $commentsCount,
            'itemCategories' => $itemCategories
        ]);
    }

    // お気に入り登録・解除処理
    public function favorite($itemId): JsonResponse
    {
        // お気に入り情報取得
        $user = Auth::user();
        $favorite = Favorite::where('item_id', $itemId)->where('user_id', $user->id)->first();

        // 解除
        if ($favorite) {
            Favorite::find($favorite->id)->delete();
            $message = 'お気に入りを解除しました';
        } else {
            $favorite = [
                'user_id' => $user->id,
                'item_id' => $itemId,
            ];
            Favorite::create($favorite);
            $message = 'お気に入りに登録しました';
        }

        return response()->json(['message' => $message]);
    }

    // コメント追加処理
    public function comment(CommentRequest $request): JsonResponse
    {
        $user = Auth::user();
        $comment = [
            'comment' => $request->comment,
            'user_id' => $user->id,
            'item_id' => $request->item_id,
        ];
        $newComment = Comment::create($comment);

        return response()->json([
            'message' => 'コメントを送信しました',
            'comment' => $newComment
        ]);
    }
}
