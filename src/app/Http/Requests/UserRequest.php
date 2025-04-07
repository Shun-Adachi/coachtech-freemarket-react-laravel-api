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
        $rules = [
            'name' => 'required',
            'current_post_code' => 'required | regex:/^\d{3}-\d{4}$/',
            'current_address' => 'required',
        ];

        if (!$this->hasFile('image') && $this->temp_image) {
            // 一時保存されている場合はチェックをスキップ
            $rules['image'] = [];
        } else {
            // 通常のバリデーションルール
            $rules['image'] = ['mimes:jpg,jpeg,png'];
        }

        return $rules;
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

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        handleTempImageUpload($this, $validator);
        parent::failedValidation($validator);
    }
}
