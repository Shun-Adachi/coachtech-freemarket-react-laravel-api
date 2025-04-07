<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\LoginRequest;
use App\Mail\LoginNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CustomAuthenticatedSessionController extends Controller
{

    // ログインフォームを表示

    public function create()
    {
        return view('auth.login');
    }

    // ログイン処理

    public function store(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only(['email', 'password']);
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Auth::validate($credentials)) {
            return response()->json([
                'error' => 'パスワードが間違っています'
            ], 401);
        }

        // 一時トークンを生成
        $token = Str::random(40);
        $user->login_token = $token;
        $user->save();

        // 認証メールを送信
        Mail::to($user->email)->send(new LoginNotification($token));

        return response()->json([
            'message' => 'ログインメールを送信しました'
        ]);
    }

    // 認証ログイン
    public function verifyLogin(Request $request): JsonResponse
    {
        $token = $request->query('token');
        $user = User::where('login_token', $token)->first();

        if (!$user) {
            return response()->json([
                'error' => 'ログインに失敗しました'
            ], 401);
        }

        // トークンを無効化し、ログイン
        $user->login_token = null;
        $user->save();
        Auth::login($user);

        return response()->json([
            'message' => 'ログインしました',
            'user' => $user
        ]);
    }
}
