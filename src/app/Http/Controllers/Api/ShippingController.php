<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\EditAddressRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class ShippingController extends Controller
{
    /**
     * ログインユーザーの配送先情報を返す
     */
    public function show(string $item_id)
    {
        $user = Auth::user();

        return response()->json([
            'shippingDefaults' => [
                'shipping_post_code'  => $user->shipping_post_code,
                'shipping_address'  => $user->shipping_address,
                'shipping_building' => $user->shipping_building,
            ],
        ], 200);
    }

    /**
     * 配送先情報の更新処理
     */
    public function update(EditAddressRequest $request, string $item_id)
    {
        $user = User::findOrFail(Auth::id());

        $currentData = [
            'shipping_post_code' => $user->shipping_post_code,
            'shipping_address'   => $user->shipping_address,
            'shipping_building'  => $user->shipping_building,
        ];

        $updateData = [
            'shipping_post_code' => $request->shipping_post_code,
            'shipping_address'   => $request->shipping_address,
            'shipping_building'  => $request->shipping_building,
        ];

        // 変更なし
        if ($currentData == $updateData) {
            return response()->json([
                'message' => '配送先に変更はありませんでした',
                'shippingDefaults' => $currentData,
            ], 200);
        }

        // 更新実行
        $user->update($updateData);

        return response()->json([
            'message' => '配送先住所を変更しました',
            'shippingDefaults' => $updateData,
        ], 200);
    }
}
