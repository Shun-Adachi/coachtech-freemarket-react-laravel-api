<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TradeMessageRequest extends FormRequest
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

    public function rules()
    {
        return [
            'message' => 'required|string|max:400',
            'image'   => 'nullable|mimes:jpeg,png',
        ];
    }

    public function messages()
    {
        return [
            'message.required' => '本文を入力してください',
            'message.max'      => '本文は400文字以内で入力してください。',
            'image.mimes'      => '「.png」または「.jpeg」形式でアップロードしてください',
        ];
    }
}
