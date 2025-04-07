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
     * 名前が入力されていない場合、バリデーションメッセージが表示される
     *
     * @return void
     */
    public function test_name_is_required_for_registration()
    {
        $formData = [
            'name' => '',
            'email' => 'example@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
        $response = $this->post('/register', $formData);
        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
        ]);
    }

    /**
     * メールアドレスが入力されていない場合、バリデーションメッセージが表示される
     *
     * @return void
     */
    public function test_email_is_required_for_registration()
    {
        $formData = [
            'name' => 'テスト太郎',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
        $response = $this->post('/register', $formData);
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /**
     * パスワードが入力されていない場合、バリデーションメッセージが表示される
     *
     * @return void
     */
    public function test_password_is_required_for_registration()
    {
        $formData = [
            'name' => 'テスト太郎',
            'email' => 'example@example.com',
            'password' => '',
            'password_confirmation' => '',
        ];
        $response = $this->post('/register', $formData);
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    /**
     * パスワードが7文字以下の場合、バリデーションメッセージが表示される
     *
     * @return void
     */
    public function test_password_must_be_at_least_eight_characters()
    {
        $formData = [
            'name' => 'テスト太郎',
            'email' => 'example@example.com',
            'password' => 'pass123',
            'password_confirmation' => 'pass123',
        ];
        $response = $this->post('/register', $formData);
        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);
    }

    /**
     * パスワードが確認用パスワードと一致しない場合、バリデーションメッセージが表示される
     *
     * @return void
     */
    public function test_password_must_match_confirmation()
    {
        $formData = [
            'name' => 'テスト太郎',
            'email' => 'example@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password456',
        ];
        $response = $this->post('/register', $formData);
        $response->assertSessionHasErrors([
            'password' => 'パスワードと一致しません',
        ]);
    }

    /**
     * 全ての項目が入力されている場合、会員情報が登録され、プロフィール画面に遷移する
     *
     * @return void
     */
    public function test_successful_registration_redirects_to_profile_screen()
    {
        // テストデータ送信
        $formData = [
            'name' => 'テスト太郎',
            'email' => 'example@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
        $response = $this->post('/register', $formData);

        // リダイレクト先および登録確認
        $response->assertRedirect('/mypage/profile');
        $this->assertDatabaseHas('users', [
            'name' => 'テスト太郎',
            'email' => 'example@example.com',
        ]);

        // パスワードがHASHからされていることの確認
        $user = User::where('email', 'example@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('password123', $user->password));
    }
}
