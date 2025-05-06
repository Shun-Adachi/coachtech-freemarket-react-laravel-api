<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
    public function rules()
    {
        return [
            'name'              => ['required', 'string', 'max:255'],
            'current_post_code' => ['required', 'regex:/^\d{3}-\d{4}$/'],
            'current_address'   => ['required', 'string', 'max:255'],
            'current_building'  => ['nullable', 'string', 'max:255'],
            // ここで一度だけチェック。React 側は常に permanent upload なので temp_image は不要
            'image'             => ['nullable', 'mimes:jpg,jpeg,png'],
        ];
    }
    public function messages()
    {
        return [
            'image.mimes' => '画像ファイルはJPEGもしくはPNG形式を選択してください',
            'name.required' => 'ユーザー名を入力してください',
            'current_post_code.required' => '郵便番号を入力してください',
            'current_post_code.regex' => '郵便番号は8文字(ハイフンあり)の形で入力してください',
            'current_address.required' => '住所を入力してください',
        ];
    }

}
