<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller as BaseController;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class RegisterController extends BaseController
{
    /**
     * API 会員登録
     * POST /api/register
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        // バリデーション済みデータ取得
        $data = $request->validated();

        // ユーザー作成
        User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'payment_method_id' => config('constants.default_method_id'),
        ]);

        return response()->json([
            'message' => '登録が完了しました。'
        ], 201);
    }
}
