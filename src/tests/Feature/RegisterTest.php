<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Models\User;

class RegisterTest extends TestCase
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
     * POST /api/register でお名前が空だとバリデーションエラーになる
     *
     * @return void
     */
    public function test_name_is_required_for_registration_via_api()
    {
        // 1) API に空の name を含むリクエストを投げる
        $payload = [
            'name'                  => '',
            'email'                 => 'example@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/register', $payload);

        // 2) 422 と name のバリデーションエラーを検証
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name'])
            ->assertJsonFragment([
                'name' => ['お名前を入力してください'],
            ]);
    }

    /**
     * POST /api/register でメールアドレスが空だとバリデーションエラーになる
     *
     * @return void
     */
    public function test_email_is_required_for_registration_via_api()
    {
        // 1) リクエストペイロードを用意
        $payload = [
            'name'                  => 'テスト太郎',
            'email'                 => '',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ];

        // 2) API に JSON で送信
        $response = $this->postJson('/api/register', $payload);

        // 3) ステータス 422 と email のバリデーションエラーを検証
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonFragment([
                'email' => ['メールアドレスを入力してください'],
            ]);
    }

    /**
     * POST /api/register でパスワードが空だとバリデーションエラーになる
     *
     * @return void
     */
    public function test_password_is_required_for_registration_via_api()
    {
        // 1) リクエストペイロードを用意
        $payload = [
            'name'                  => 'テスト太郎',
            'email'                 => 'example@example.com',
            'password'              => '',
            'password_confirmation' => '',
        ];

        // 2) API に JSON で送信
        $response = $this->postJson('/api/register', $payload);

        // 3) ステータス 422 と password のバリデーションエラーを検証
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password'])
            ->assertJsonFragment([
                'password' => ['パスワードを入力してください'],
            ]);
    }

    /**
     * POST /api/register でパスワードが7文字以下だとバリデーションエラーになる
     *
     * @return void
     */
    public function test_password_must_be_at_least_eight_characters_via_api()
    {
        // 1) JSON ペイロードを用意
        $payload = [
            'name'                  => 'テスト太郎',
            'email'                 => 'example@example.com',
            'password'              => 'pass123',      // 7文字
            'password_confirmation' => 'pass123',
        ];

        // 2) JSON リクエストで /api/register に送信
        $response = $this->postJson('/api/register', $payload);

        // 3) 422 + password のエラーを検証
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password'])
            ->assertJsonFragment([
                'password' => ['パスワードは8文字以上で入力してください'],
            ]);
    }

    /**
     * POST /api/register でパスワード確認が一致しないとバリデーションエラーになる
     *
     * @return void
     */
    public function test_password_must_match_confirmation_via_api()
    {
        // 1) JSON ペイロードを用意（password と confirmation が不一致）
        $payload = [
            'name'                  => 'テスト太郎',
            'email'                 => 'example@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password456',
        ];

        // 2) JSON リクエストで /api/register に送信
        $response = $this->postJson('/api/register', $payload);

        // 3) ステータス 422 + password のエラーを検証
        $response->assertStatus(422)
            // errors.password があるか
            ->assertJsonValidationErrors(['password'])
            // レスポンス内のエラーメッセージを確認
            ->assertJsonFragment([
                'password' => ['パスワードと一致しません'],
            ]);
    }

    /**
     * POST /api/register で全項目が正しく入力されている場合に
     * 会員情報が登録される
     *
     * @return void
     */
    public function test_successful_registration_creates_user_via_api()
    {
        // 1) 登録用ペイロード（password_confirmation も含む）
        $payload = [
            'name'                  => 'テスト太郎',
            'email'                 => 'example@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ];

        // 2) JSON リクエストで API に送信
        $response = $this->postJson('/api/register', $payload);

        // 3) ステータス 201 とメッセージを検証
        $response->assertCreated()
            ->assertJson(['message' => '登録が完了しました。']);

        // 4) データベースにユーザーが保存されていることを確認
        $this->assertDatabaseHas('users', [
            'name'  => 'テスト太郎',
            'email' => 'example@example.com',
        ]);

        // 5) パスワードがハッシュ化されていることを確認
        $user = User::where('email', 'example@example.com')->first();
        $this->assertNotNull($user, 'ユーザーが DB に登録されていません');
        $this->assertTrue(
            Hash::check('password123', $user->password),
            'パスワードが正しくハッシュ化されていません'
        );
    }
}
