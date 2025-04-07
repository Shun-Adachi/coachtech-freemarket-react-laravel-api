<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Mailable;
use Tests\TestCase;
use App\Models\User;

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
     * メールアドレスが入力されていない場合、バリデーションメッセージが表示される
     *
     * @return void
     */
    public function test_email_is_required_for_login()
    {

        $formData = [
            'email' => '',
            'password' => 'rootroot',
        ];
        $response = $this->post('/login', $formData);
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /**
     * パスワードが入力されていない場合、バリデーションメッセージが表示される
     *
     * @return void
     */
    public function test_password_is_required_for_login()
    {

        $formData = [
            'email' => 'root@example.com',
            'password' => '',
        ];
        $response = $this->post('/login', $formData);
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }


    /**
     * 入力情報が間違っている場合、バリデーションメッセージが表示される
     *
     * @return void
     */
    public function test_invalid_login_shows_validation_message()
    {

        $formData = [
            'email' => 'notregistered@example.com',
            'password' => 'wrongpassword',
        ];
        $response = $this->post('/login', $formData);
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }


    /**
     * 正しい情報が入力された場合メールが送信され、メールに記載のURLからログイン出来る
     *
     * @return void
     */
    public function test_login_sends_verification_email_and_verifies_login()
    {

        Mail::fake();

        // ログインリクエストを送信
        $formData = [
            'email' => 'root@example.com',
            'password' => 'rootroot',
        ];
        $user = User::where('email', $formData['email'])->first();
        $response = $this->post('/login', $formData);

        // メール送信後のリダイレクト先とメッセージの確認
        $response->assertRedirect('/login');
        $response->assertSessionHas('message', 'ログインメールを送信しました');

        // メール送信の確認
        Mail::assertSent(function (Mailable $mail) use ($user) {
            return $mail->hasTo('root@example.com');
        });

        // トークンが生成され、保存されていることを確認
        $user->refresh();
        $this->assertNotNull($user->login_token);

        // メールの認証リンクにアクセス
        $token = $user->login_token;
        $verifyResponse = $this->get("/verify-login?token={$token}");

        // 認証ログインが成功し、リダイレクトされることを確認
        $verifyResponse->assertRedirect('/');
        $verifyResponse->assertSessionHas('message', 'ログインしました');

        // トークンが無効化されていることを確認
        $user->refresh();
        $this->assertNull($user->login_token);

        // ユーザーがログイン状態であることを確認
        $this->assertAuthenticatedAs($user);
    }
}
