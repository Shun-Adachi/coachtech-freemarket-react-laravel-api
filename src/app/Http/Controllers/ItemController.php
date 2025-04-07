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

class ItemController extends Controller
{

    // 商品一覧ページ表示
    public function index(Request $request)
    {
        // ユーザー情報・タブ情報・検索取得
        $user = Auth::user();
        $userId = $user->id ?? null;
        $tab = $request->tab;
        if ($request->isMethod('post')) {
            $keyword = $request->input('keyword', '');
            session()->put('keyword', $keyword);
        } elseif (session('keyword')) {
            $keyword = session('keyword');
        } else {
            $keyword = "";
        }

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

        return view('index', compact('items', 'tab', 'soldItemIds'));
    }

    // 商品詳細ページ表示
    public function show(Request $request, $itemId)
    {
        // ユーザー情報取得
        $user = Auth::user();
        $userId = $user->id ?? null;

        // 商品詳細取得
        $item = Item::with(['user', 'condition'])->where('id', $itemId)->first();
        $purchase =  Purchase::where('item_id', $itemId)->exists();

        // 円形式変換
        $item->price = number_format($item->price);

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

        return view('item', compact('item', 'purchase', 'isFavorite', 'favoritesCount', 'comments', 'commentsCount', 'itemCategories'));
    }

    // お気に入り登録・解除処理
    public function favorite($itemId)
    {
        // お気に入り情報取得
        $user = Auth::user();
        $favorite = Favorite::where('item_id', $itemId)->where('user_id', $user->id)->first();

        // 解除
        if ($favorite) {
            Favorite::find($favorite->id)->delete();
        }
        // 登録
        else {
            $favorite = [
                'user_id' => $user->id,
                'item_id' => $itemId,
            ];
            Favorite::create($favorite);
        }

        return redirect()->back();
    }

    // コメント追加処理
    public function comment(CommentRequest $request)
    {
        $user = Auth::user();
        $comment = [
            'comment' => $request->comment,
            'user_id' => $user->id,
            'item_id' => $request->item_id,
        ];
        Comment::create($comment);

        return redirect()->back()->with('message', 'コメントを送信しました');
    }
}
