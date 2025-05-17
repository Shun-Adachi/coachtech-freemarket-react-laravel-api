<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * シーディングを有効にするための設定
     *
     * @return bool
     */
    protected function shouldSeed()
    {
        return true;
    }

    /**
     * ログアウトができる（API）
     *
     * @return void
     */
    public function test_user_can_logout_via_api()
    {
        // 1) テスト用ユーザーを生成（または既存ユーザーを取得）
        $user = User::where('email', 'root@example.com')->first();

        // 2) トークンを発行
        $token = $user->createToken('test-token')->plainTextToken;

        // 3) ログアウト API を叩く
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout');

        // 4) レスポンス確認
        $response->assertOk()
            ->assertJson(['message' => 'ログアウトしました。']);

        // 5) トークンが削除されたことを確認
        $this->assertNull(PersonalAccessToken::findToken($token));

        // ★ここでアプリケーションをリフレッシュ！
        $this->refreshApplication();

        // 6) 同じトークンで保護ルートにアクセスすると 401 になることを確認
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user')   // 認証済みユーザー情報取得用エンドポイント
            ->assertUnauthorized();
    }
}
