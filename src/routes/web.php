<?php

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


// ① 認証不要のページ（React のエントリポイントを返すだけにする）
Route::get('/{any}', function () {
    // ここでは React ビルド後の index.html を返す
    return view('spa');
})->where('any', '.*');