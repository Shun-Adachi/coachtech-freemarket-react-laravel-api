<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EditAddressRequest extends FormRequest
{
   public function rules()
    {
        return [
            'shipping_post_code'  => ['required','regex:/^\d{3}-\d{4}$/'],
            'shipping_address'    => ['required','string','max:255'],
            'shipping_building'   => ['nullable','string','max:255'],
        ];
    }
    /**
     * カスタムメッセージ
     */
    public function messages()
    {
        return [
            'shipping_post_code.required' => '郵便番号は必須です。',
            'shipping_post_code.regex'    => '郵便番号は「000-0000」の形式で入力してください。',
            'shipping_address.required'   => '住所は必須です。',
            'shipping_address.max'        => '住所が長すぎます（255文字以内）。',
            'shipping_building.max'       => '建物名・部屋番号が長すぎます（255文字以内）。',
        ];
    }
}
