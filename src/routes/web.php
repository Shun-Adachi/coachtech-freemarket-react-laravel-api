<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\SellController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\CustomAuthenticatedSessionController;
use App\Http\Controllers\TradeController;
use App\Http\Controllers\TradeMessageController;
use App\Http\Controllers\TradeRatingController;
use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

//Fortify カスタマイズログイン
Route::get('/login', [CustomAuthenticatedSessionController::class, 'create'])->middleware('guest')->name('login');
Route::post('/login', [CustomAuthenticatedSessionController::class, 'store']);
Route::get('/verify-login', [CustomAuthenticatedSessionController::class, 'verifyLogin']);

//未認証ユーザー
Route::prefix('/')->group(function () {
    Route::get('/', [ItemController::class, 'index']);
    Route::post('/', [ItemController::class, 'index']);
    //セッションのクリア
    Route::middleware(['clear.session'])->group(function () {
        Route::get('/item/{item_id}', [ItemController::class, 'show']);
    });
});

//認証ユーザー
Route::middleware('auth')->group(function () {
    Route::get('/purchase/buy', [PurchaseController::class, 'buy']);
    //セッションのクリア
    Route::middleware(['clear.session'])->group(function () {
        Route::get('/item/favorite/{item_id}', [ItemController::class, 'favorite']);
        Route::post('/item/comment', [ItemController::class, 'comment']);
        Route::post('/purchase/checkout', [PurchaseController::class, 'createCheckoutSession'])->name('checkout.session');
        Route::patch('/purchase/address/update', [PurchaseController::class, 'update']);
        Route::get('/purchase/address', [PurchaseController::class, 'edit']);
        Route::post('/purchase/address', [PurchaseController::class, 'edit']);
        Route::get('/purchase/{item_id}', [PurchaseController::class, 'purchase'])->name('purchase');
        Route::get('/sell', [SellController::class, 'sell']);
        Route::post('/sell/create', [SellController::class, 'store']);
        Route::get('/mypage', [UserController::class, 'index']);
        Route::get('/mypage/profile', [UserController::class, 'edit']);
        Route::patch('/mypage/profile/update', [UserController::class, 'update']);
        Route::get('/logout', [UserController::class, 'logout'])->name('logout');
        Route::get('/trades/{trade}/messages', [TradeMessageController::class, 'index']);
        Route::post('/trades/{trade}/messages', [TradeMessageController::class, 'store']);
        Route::post('/trades/{trade}/messages/edit/{message}', [TradeMessageController::class, 'edit']);
        Route::patch('/trades/{trade}/messages/{message}', [TradeMessageController::class, 'update']);
        Route::delete('/trades/{trade}/messages/{message}', [TradeMessageController::class, 'destroy']);
        Route::post('/trades/{trade}/complete', [TradeController::class, 'complete']);
        Route::post('/trades/{trade}/rate', [TradeRatingController::class, 'store']);
    });
});
