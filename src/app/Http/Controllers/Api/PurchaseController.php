<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseRequest;
use App\Models\{Item, Purchase, PaymentMethod, Trade};
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseController extends Controller
{
    /**
     * 購入確定
     * POST /api/purchase
     * 認証必須（auth:sanctum）
     */
    public function store(PurchaseRequest $request): Response
    {
        $buyer = $request->user();                 // ログインユーザ
        $item  = Item::lockForUpdate()->findOrFail($request->item_id);

        // ─── バリデーションの追加チェック ─────────────────────
        if (Purchase::where('item_id', $item->id)->exists()) {
            return response([
                'message' => 'この商品はすでに取引済みです。',
            ], 409);
        }

        if ($item->user_id === $buyer->id) {
            return response([
                'message' => '自分が出品した商品は購入できません。'
            ], 403);
        }

        /** DB トランザクション */
        DB::transaction(function () use ($request, $item, $buyer) {

            // 1) purchases テーブルへレコード追加
            /** @var \App\Models\Purchase $purchase */
            $purchase = Purchase::create([
                'user_id'        => $buyer->id,
                'item_id'        => $item->id,
                'payment_method_id' => $request->payment_method,
                'shipping_post_code'  => $request->shipping_post_code,
                'shipping_address'  => $request->shipping_address,
                'shipping_building' => $request->shipping_building
            ]);

            // 2) trades テーブルへレコード追加
            Trade::create([
                'purchase_id' => $purchase->id,
                'is_complete' => false,
            ]);
        });

        return response([
            'message' => '購入が確定しました。',
        ], 201);
    }

    public function show(Request $request, int $item_id): Response
    {
        $user = $request->user();
        $item = Item::with('user')->findOrFail($item_id);

        // ─── 出品者は購入画面を見れない ───────────────────
        $item = Item::findOrFail($item_id);
        if ($item->user_id === $user->id) {
            return response([
                'message' => '自分が出品した商品は購入できません。',
            ], 403);
        }

        // ─── 売り切れチェック（既存ロジック） ─────────
        if (Purchase::where('item_id', $item_id)->exists()) {
            return response([
                'message' => 'この商品はすでに取引済みです。',
            ], 403);
        }

        return response([
            'item' => [
                'id'        => $item->id,
                'name'      => $item->name,
                'image_url' => asset('storage/'.$item->image_path),
                'price'     => (int) $item->price,
                'seller'    => [
                    'id'   => $item->user->id,
                    'name' => $item->user->name,
                ],
            ],
            'paymentMethods'   => PaymentMethod::all(['id','name']),
            'shippingDefaults' => [
                'shipping_post_code'  => $user->shipping_post_code,
                'shipping_address'  => $user->shipping_address,
                'shipping_building' => $user->shipping_building,
            ],
        ], 200);
    }
}
