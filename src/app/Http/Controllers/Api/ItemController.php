<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\Purchase;

class ItemController extends BaseController
{
    /**
     * 商品一覧を取得（おすすめ／マイリスト対応）
     * GET /api/items?tab={tab}
     */
    public function index(Request $request)
    {
        $tab = $request->query('tab', '');

        if ($tab === 'mylist') {
            // マイリスト: 認証済なら購入履歴のアイテムを、それ以外は空配列
            if ($request->user()) {
                $itemIds = Purchase::where('user_id', $request->user()->id)
                    ->pluck('item_id');
                $items = Item::whereIn('id', $itemIds)->get();
            } else {
                $items = collect();
            }
        } else {
            // おすすめ: 全商品
            $items = Item::all();
        }

        // JSON 用に整形
        $data = $items->map(function (Item $item) {
            $sold = Purchase::where('item_id', $item->id)->exists();
            return [
                'id'            => $item->id,
                'name'          => $item->name,
                'image_url'     => asset('storage/' . $item->image_path),
                'is_sold'       => $sold,
                'message_count' => null, // 必要なら別エンドポイントで提供
            ];
        });

        return response()->json($data);
    }
}
