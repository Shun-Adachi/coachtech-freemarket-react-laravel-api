<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use App\Models\User;
use App\Mail\LoginCodeMail;
use App\Models\LoginCode;

class LoginTest extends TestCase
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
     * メールアドレスが入力されていない場合、JSON 422 と検証エラーを返す
     *
     * @return void
     */
    public function test_email_is_required_for_login_via_api()
    {
        // パスワードだけは適当な文字列を入れておく
        $payload = [
            'email'    => '',
            'password' => 'dummy-password',
        ];

        // API に対して JSON リクエスト
        $response = $this->postJson('/api/request-login-code', $payload);

        // ステータス 422
        $response->assertStatus(422);

        // バリデーションエラーとして email が返っていること
        $response->assertJsonValidationErrors(['email']);

        // メッセージも確認したい場合
        $this->assertEquals(
            'メールアドレスを入力してください',
            $response->json('errors.email.0')
        );
    }

    /**
     * パスワードが入力されていない場合、JSON 422 と検証エラーを返す
     *
     * @return void
     */
    public function test_password_is_required_for_login_via_api()
    {
        $payload = [
            'email'    => 'root@example.com',
            'password' => '',
        ];

        // メール＋パスワードで認証コードをリクエストするエンドポイント
        $response = $this->postJson('/api/request-login-code', $payload);

        // ステータス 422
        $response->assertStatus(422);

        // password フィールドに検証エラーがあること
        $response->assertJsonValidationErrors(['password']);

        // エラーメッセージをピンポイントで確認したい場合
        $this->assertEquals(
            'パスワードを入力してください',
            $response->json('errors.password.0')
        );
    }


    /**
     * 入力情報が誤っている場合、401 とエラーメッセージを返す
     *
     * @return void
     */
    public function test_invalid_login_shows_error_via_api()
    {
        $payload = [
            'email'    => 'notregistered@example.com',
            'password' => 'wrongpassword',
        ];

        $response = $this->postJson('/api/request-login-code', $payload);

        // 認証失敗時は 401 を返し、メッセージに「認証に失敗しました。」が含まれる
        $response->assertStatus(401)
            ->assertJson(['message' => '認証に失敗しました。']);
    }

    /**
     * 正しい情報が入力された場合、認証コードメールが送信され、
     * そのコードでログイン用トークンが発行される
     *
     * @return void
     */
    public function test_login_sends_code_and_verifies_login_via_api()
    {
        // メール送信をモック
        Mail::fake();

        // シーディング済みのユーザーを取得（seed で root@example.com がいる想定）
        $user = User::where('email', 'root@example.com')->firstOrFail();

        // 1) 認証コードリクエスト
        $payload = [
            'email'    => 'root@example.com',
            'password' => 'rootroot',
        ];
        $res1 = $this->postJson('/api/request-login-code', $payload);

        // → 200 とメッセージを返す
        $res1->assertOk()
            ->assertJson(['message' => '認証コードをメールで送信しました']);

        // → 正しい宛先にメールが送られている
        Mail::assertSent(LoginCodeMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });

        // → DB にコードが保存されている
        $record = LoginCode::where('email', $user->email)->first();
        $this->assertNotNull($record, 'LoginCode レコードが見つかりません');

        // 2) コード検証リクエスト
        $verifyPayload = array_merge($payload, [
            'code' => $record->code,
        ]);
        $res2 = $this->postJson('/api/verify-login-code', $verifyPayload);

        // → 200 と user＋token を返す
        $res2->assertOk()
            ->assertJsonStructure([
                'user'  => ['id', 'name', 'email'],
                'token',
            ]);

        // → 発行されたトークンで認証済み API にアクセスできる
        $token = $res2->json('token');
        $this->withToken($token)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJson(['id' => $user->id]);
    }
}
