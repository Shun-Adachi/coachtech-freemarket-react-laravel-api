<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Item;
use App\Models\Category;
use App\Models\CategoryItem;
use App\Models\Condition;
use App\Http\Requests\SellRequest;

class SellController extends Controller
{

    // 出品画面表示
    public function sell(Request $request)
    {
        $user = Auth::user();
        $categories = Category::get();
        $conditions = Condition::get();

        return view('sell', compact('user', 'categories', 'conditions'));
    }

    // 出品処理
    public function store(SellRequest $request)
    {
        $user = Auth::user();

        // 一時保存された画像の利用
        $imagePath = $request->file('image')
            ? $request->file('image')->store('images/items', 'public')
            : moveTempImageToPermanentLocation($request->temp_image, 'images/items/');

        // 商品データの保存
        $item = Item::create([
            'name' => $request->name,
            'description' => $request->description,
            'user_id' => $user->id,
            'condition_id' => $request->condition,
            'price' => $request->price,
            'image_path' => $imagePath,
        ]);

        // カテゴリと商品の関連を保存
        foreach ($request->categories as $categoryId) {
            CategoryItem::create([
                'item_id' => $item->id,
                'category_id' => $categoryId,
            ]);
        }

        return redirect('/mypage?tab=sell')->with('message', '商品を出品しました');
    }
}
