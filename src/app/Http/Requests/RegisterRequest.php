<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'email', 'max:255', 'unique:users'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            // 'confirmed' を使うと password_confirmation フィールドも自動検証
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'ユーザー名を入力してください',
            'email.required'    => 'メールアドレスを入力してください',
            'email.email'       => '正しいメールアドレス形式で入力してください',
            'email.unique'      => 'そのメールアドレスは既に使用されています',
            'password.required' => 'パスワードを入力してください',
            'password.min'      => 'パスワードは8文字以上で入力してください',
            'password.confirmed'=> '確認用パスワードと一致しません',
        ];
    }
}
