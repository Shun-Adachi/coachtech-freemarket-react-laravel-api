<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TradeMessageUpdateRequest extends FormRequest
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
            'updateMessage' => 'required|string|max:400',
        ];
    }

    public function messages()
    {
        return [
            'updateMessage.required' => '本文を入力してください',
            'updateMessage.max'      => '本文は400文字以内で入力してください。',
        ];
    }

}
