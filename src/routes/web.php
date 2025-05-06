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

Route::get('/purchase/{item_id}', function () {
    return response()->file(public_path('index.html'));
});

Route::get('/purchase/{item_id}/complete', function () {
    return response()->file(public_path('index.html'));
});

Route::get('/{any}', function () {
    // ここでは React ビルド後の index.html を返す
    return view('spa');
})->where('any', '.*');