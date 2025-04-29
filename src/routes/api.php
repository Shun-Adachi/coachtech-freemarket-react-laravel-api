<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\ItemController as ApiItemController;
use App\Http\Controllers\Api\SellController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\TradeController;
use App\Http\Controllers\Api\TradeMessageController;
use App\Http\Controllers\Api\TradeRatingController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RegisterController;

// 認証前に必要なエンドポイント

Route::post('/request-login-code', [AuthController::class, 'requestLoginCode']);
Route::post('/verify-login-code', [AuthController::class, 'verifyLoginCode'])->name('api.verify-login-code');
Route::post('/register', [RegisterController::class, 'register']);

// 商品一覧／詳細
Route::get('/items', [ApiItemController::class, 'index']);

Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

// 認証が必要なグループ
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/mypage', [UserProfileController::class, 'index']);
    // ユーザー情報
    Route::get('/user', [UserProfileController::class, 'show']);

    // ユーザー情報更新 (multipart/form-data で thumbnail も OK)
    Route::match(['put', 'patch'], '/user', [UserProfileController::class, 'update']);

    // サムネイルのみ削除
    Route::delete('/user/thumbnail', [UserProfileController::class, 'deleteThumbnail']);

    // 商品一覧／詳細
    // Route::get('/items', [ItemController::class, 'index']);
    // Route::get('/items/{item}', [ItemController::class, 'show']);

    // 出品
    Route::post('/items', [SellController::class, 'store']);

    // 購入処理
    Route::post('/purchase/checkout', [PurchaseController::class, 'createCheckoutSession']);
    Route::get('/purchase/{item}', [PurchaseController::class, 'purchase']);
    Route::patch('/purchase/address', [PurchaseController::class, 'update']);

    // 取引・メッセージ・評価
    Route::post('/trades/{trade}/complete', [TradeController::class, 'complete']);
    Route::post('/trades/{trade}/rate', [TradeRatingController::class, 'store']);

    Route::apiResource('trades.messages', TradeMessageController::class)
         ->only(['index','store','update','destroy']);
});
