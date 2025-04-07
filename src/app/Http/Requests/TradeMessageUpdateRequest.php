<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

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

    /**
     * バリデーション失敗時に、編集対象のメッセージIDをフラッシュする
     */
    protected function failedValidation(Validator $validator)
    {
        // ルートパラメータから編集対象のメッセージ（モデル）のIDを取得
        $message = $this->route('message');
        if ($message) {
            session()->flash('editingMessageId', $message->id);
        }
        throw new HttpResponseException(
            redirect()->back()->withInput()->withErrors($validator)
        );
    }
}
