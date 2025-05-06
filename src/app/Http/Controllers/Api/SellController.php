<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Item;
use App\Models\CategoryItem;
use App\Models\Category;
use App\Models\Condition;
use App\Http\Requests\SellRequest;

class SellController extends Controller
{
    /**
     * 出品フォーム表示用データを返す
     * GET /api/sell
     */
    public function create(Request $request): JsonResponse
    {
        $user       = $request->user();
        $categories = Category::select('id', 'name')->get();
        $conditions = Condition::select('id', 'name')->get();

        return response()->json([
            'user'       => [
                'id'            => $user->id,
                'name'          => $user->name,
                'thumbnail_url' => $user->thumbnail_path
                    ? asset('storage/' . $user->thumbnail_path)
                    : null,
            ],
            'categories' => $categories,
            'conditions' => $conditions,
        ], 200);
    }

    /**
     * 出品処理
     * POST /api/sell
     * 一時保存された画像パスを前提とする
     */
    public function store(SellRequest $request): JsonResponse
    {
        $user = $request->user();

        // フロント側で保存された一時画像パスを取得
        $imageFile = $request->file('image');
        $imagePath = $imageFile->store('images/items', 'public');

        // 商品登録
        $item = Item::create([
            'name'         => $request->input('name'),
            'description'  => $request->input('description'),
            'user_id'      => $user->id,
            'condition_id' => $request->input('condition_id'),
            'price'        => $request->input('price'),
            'image_path'   => $imagePath,
        ]);

        // カテゴリ関連付け
        foreach ($request->input('categories', []) as $categoryId) {
            CategoryItem::create([
                'item_id'     => $item->id,
                'category_id' => $categoryId,
            ]);
        }

        return response()->json([
            'message' => '商品を出品しました',
            'item'    => [
                'id'           => $item->id,
                'name'         => $item->name,
                'image_url'    => asset('storage/' . $item->image_path),
                'price'        => (int)$item->price,
                'condition_id' => $item->condition_id,
                'category_ids' => $request->input('category_ids', []),
            ],
        ], 201);
    }
}
