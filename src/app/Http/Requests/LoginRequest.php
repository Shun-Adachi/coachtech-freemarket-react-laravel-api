<?php

namespace App\Http\Requests;

use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;


class LoginRequest extends FortifyLoginRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        // verify-login-code のときは code も必須
        if ($this->route()->getName() === 'api.verify-login-code') {
            return [
                'email'    => ['required','email'],
                'password' => ['required','string','min:8'],
                'code'     => ['required','digits:6'],
            ];
        }

        // request-login-code のときは email/password
        if ($this->route()->getName() === 'api.request-login-code') {
            return [
                'email'    => ['required','email'],
                'password' => ['required','string','min:8'],
            ];
        }

        // フォールバック
        return [
            'email'    => ['required','email'],
            'password' => ['required','string','min:8'],
        ];
    }


    public function messages()
    {
        return [
            'email.required' => 'メールアドレスを入力してください',
            'email.email' => 'ユーザー名@ドメイン形式で入力してください',
            'password.required' => 'パスワードを入力してください',
            'password.min' => 'パスワードは8文字以上で入力してください',
        ];
    }
}
