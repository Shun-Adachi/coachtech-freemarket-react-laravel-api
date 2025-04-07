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

class CustomAuthenticatedSessionController extends Controller
{

    // ログインフォームを表示

    public function create()
    {
        return view('auth.login');
    }

    // ログイン処理

    public function store(LoginRequest $request)
    {
        $credentials = $request->only(['email', 'password']);
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Auth::validate($credentials)) {
            throw ValidationException::withMessages([
                'password' => ['パスワードが間違っています'],
            ]);
        }

        // 一時トークンを生成
        $token = Str::random(40);
        $user->login_token = $token;
        $user->save();

        // 認証メールを送信
        Mail::to($user->email)->send(new LoginNotification($token));

        return redirect()->route('login')->withInput()->with('message', 'ログインメールを送信しました');
    }

    // 認証ログイン
    public function verifyLogin(Request $request)
    {
        $token = $request->query('token');
        $user = User::where('login_token', $token)->first();

        if (!$user) {
            return redirect('/login')->withErrors(['error' => 'ログインに失敗しました']);
        }

        // トークンを無効化し、ログイン
        $user->login_token = null;
        $user->save();
        Auth::login($user);

        return redirect('/')->with('message', 'ログインしました');
    }
}
