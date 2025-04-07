<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here you may configure your settings for cross-origin resource sharing
| or "CORS". This determines what cross-origin operations may execute
| in web browsers. You are free to adjust these settings as needed.
|
*/

// 認証関連のルート
Route::post('/login', [App\Http\Controllers\CustomAuthenticatedSessionController::class, 'store']);
Route::get('/login/verify', [App\Http\Controllers\CustomAuthenticatedSessionController::class, 'verifyLogin']);
Route::post('/logout', [App\Http\Controllers\UserController::class, 'logout'])->middleware('auth:sanctum');

// 商品関連のルート
Route::get('/items', [App\Http\Controllers\ItemController::class, 'index']);
Route::get('/items/{itemId}', [App\Http\Controllers\ItemController::class, 'show']);
Route::post('/items/{itemId}/favorite', [App\Http\Controllers\ItemController::class, 'favorite'])->middleware('auth:sanctum');
Route::post('/items/{itemId}/comment', [App\Http\Controllers\ItemController::class, 'comment'])->middleware('auth:sanctum');

// 出品関連のルート
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/sell', [App\Http\Controllers\SellController::class, 'sell']);
    Route::post('/sell', [App\Http\Controllers\SellController::class, 'store']);
});

// 購入関連のルート
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/purchase/{itemId}', [App\Http\Controllers\PurchaseController::class, 'purchase']);
    Route::get('/purchase/{itemId}/edit', [App\Http\Controllers\PurchaseController::class, 'edit']);
    Route::post('/purchase/{itemId}/update', [App\Http\Controllers\PurchaseController::class, 'update']);
    Route::post('/purchase/{itemId}/checkout', [App\Http\Controllers\PurchaseController::class, 'createCheckoutSession']);
    Route::post('/purchase/buy', [App\Http\Controllers\PurchaseController::class, 'buy']);
});

// 取引関連のルート
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/trades/{trade}/complete', [App\Http\Controllers\TradeController::class, 'complete']);
    Route::get('/trades/{trade}/messages', [App\Http\Controllers\TradeMessageController::class, 'index']);
    Route::post('/trades/{trade}/messages', [App\Http\Controllers\TradeMessageController::class, 'store']);
    Route::put('/trades/{trade}/messages/{message}', [App\Http\Controllers\TradeMessageController::class, 'update']);
    Route::delete('/trades/{trade}/messages/{message}', [App\Http\Controllers\TradeMessageController::class, 'destroy']);
    Route::post('/trades/{trade}/rating', [App\Http\Controllers\TradeRatingController::class, 'store']);
});

// ユーザー関連のルート
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/mypage', [App\Http\Controllers\UserController::class, 'index']);
    Route::get('/mypage/profile', [App\Http\Controllers\UserController::class, 'edit']);
    Route::put('/mypage/profile', [App\Http\Controllers\UserController::class, 'update']);
});
