<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Http\Requests\LoginRequest;
use App\Models\LoginCode;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\LoginCodeMail;
use Illuminate\Support\Facades\Log;

class AuthController extends BaseController
{
    /**
     * API ログアウト：現在のアクセストークンを削除
     */
    public function logout(Request $request)
    { // ユーザー取得
        $user = $request->user();
        Log::info('→ bearerToken', ['token' => $request->bearerToken()]);
        Log::info('→ user()',       ['user'  => optional($request->user())->id]);
        // currentAccessToken() が null でも optional で吸収できるようにネストして呼び出し
        Log::info('→ currentAccessToken()', [
            'token' => optional(optional($user)->currentAccessToken())->id,
        ]);
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'ログアウトしました。'], 200);
    }

    // メールに認証コードを送信
    public function requestLoginCode(LoginRequest $request): JsonResponse
    {
        // バリデーション済みデータ取得
        $data = $request->validated();
        $email = $data['email'];
        $password = $data['password'];

        // ユーザーとパスワードの検証
        $user = User::where('email', $email)->first();
        if (! $user || ! Hash::check($password, $user->password)) {
            return response()->json([
                'message' => '認証に失敗しました。'
            ], 401);
        }

        // 既存コードを削除し、新規コードを生成
        LoginCode::where('email', $email)->delete();
        $code = random_int(100000, 999999);
        $expiresAt = Carbon::now()->addMinutes(10);
        LoginCode::create([
            'email'      => $email,
            'code'       => $code,
            'expires_at' => $expiresAt,
        ]);
        // メール送信
        Mail::to($email)->send(new LoginCodeMail($code, $expiresAt));

        return response()->json(['message' => '認証コードをメールで送信しました'], 200);
    }

    /**
     * 認証コードの検証とアクセストークン発行
     * POST /api/verify-login-code
     */
    public function verifyLoginCode(LoginRequest $request): JsonResponse
    {
        // バリデーション済みデータ取得
        $data = $request->validated();
        $email = $data['email'];
        $password = $data['password'];
        $code = $data['code'];

        // ユーザーとパスワード検証
        $user = User::where('email', $email)->firstOrFail();
        if (! Hash::check($password, $user->password)) {
            return response()->json([
                'message' => '認証に失敗しました。'
            ], 401);
        }

        // コードの有効性チェック
        $record = LoginCode::where('email', $email)
            ->where('code', $code)
            ->where('expires_at', '>=', Carbon::now())
            ->first();

        if (! $record) {
            return response()->json([
                'message' => '認証コードが無効または期限切れです'
            ], 422);
        }

        // 使用済みコードを削除
        $record->delete();

        // Personal Access Token を発行
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ], 200);
    }
}
