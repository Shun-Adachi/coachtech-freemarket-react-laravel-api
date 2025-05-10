<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\SellController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\TradeController;
use App\Http\Controllers\Api\TradeMessageController;
use App\Http\Controllers\Api\TradeRatingController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Api\ShippingController;
use App\Http\Controllers\Api\CheckoutController;

// 認証前に必要なエンドポイント
Route::post('/request-login-code', [AuthController::class, 'requestLoginCode']);
Route::post('/verify-login-code', [AuthController::class, 'verifyLoginCode'])->name('api.verify-login-code');
Route::post('/register', [RegisterController::class, 'register']);

// 商品一覧／詳細
Route::get('/items', [ItemController::class, 'index']);
Route::get('/item/{itemId}', [ItemController::class, 'show']);
Route::get('/item/{itemId}/comments', [ItemController::class, 'comments']);

// 認証が必要なグループ
Route::middleware('auth:sanctum')->group(function () {

    // ログアウト
    Route::post('/logout', [AuthController::class, 'logout']);
    // プロフィールページ
    Route::patch('/mypage/profile', [UserProfileController::class, 'update']);
    Route::get('/mypage', [UserProfileController::class, 'index']);
    Route::get('/mypage/profile', [UserProfileController::class, 'show']);

    // 商品詳細
    Route::post('/item/{itemId}/favorite', [ItemController::class, 'favorite']);
    Route::delete('/item/{itemId}/favorite', [ItemController::class, 'unfavorite']);
    Route::post('/item/{itemId}/comments', [ItemController::class, 'addComment']);

    // 購入詳細取得
    Route::get('/purchase/{itemId}', [PurchaseController::class, 'show']);

    // 配送先変更
    Route::get('/purchase/address/{item_id}', [ShippingController::class, 'show'])
        ->name('api.purchase.address.show');
    Route::put('/purchase/address/{item_id}', [ShippingController::class, 'update'])
        ->name('api.purchase.addrss.update');

    // 出品
    Route::get('/sell', [SellController::class, 'create'])
        ->name('api.sell.create');
    Route::post('/sell', [SellController::class, 'store'])
        ->name('api.sell.store');

    // Stripe Checkout セッション作成 API
    Route::post('/purchase/{item_id}/checkout', [CheckoutController::class, 'createCheckoutSession'])
        ->name('api.purchase.checkout');

    // 購入処理
    Route::post('/purchase', [PurchaseController::class, 'store'])
        ->name('api.purchase.store');


    // 取引チャット
    Route::get('/trades/{trade}/messages', [TradeMessageController::class, 'index'])->name('api.trades.messages.index');
    Route::post('/trades/{trade}/messages', [TradeMessageController::class, 'store'])->name('api.trades.messages.store');
    Route::put('/trades/{trade}/messages/{message}', [TradeMessageController::class, 'update'])->name('api.trades.messages.update');
    Route::delete('/trades/{trade}/messages/{message}', [TradeMessageController::class, 'destroy'])->name('api.trades.messages.destroy');

    // 取引完了・評価
    Route::post('/trades/{trade}/complete', [TradeController::class, 'complete']);
    Route::post('/trades/{trade}/rate', [TradeRatingController::class, 'store']);

});
