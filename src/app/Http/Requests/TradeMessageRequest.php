<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
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
            'image'   => 'nullable|file|image|mimes:jpeg,png',
        ];
    }

    public function messages()
    {
        return [
            'message.required' => '本文を入力してください',
            'message.max'      => '本文は400文字以内で入力してください。',
            'image.file'       => 'ファイルをアップロードしてください。',
            'image.image'      => 'アップロードできるのは画像ファイルのみです。',
            'image.mimes'      => '「.png」または「.jpeg」形式でアップロードしてください。',
        ];
    }

    /**
     * 拡張子チェックを追加で行う
     */
    protected function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            if ($file = $this->file('image')) {
                // 元のファイル名の拡張子
                $ext = strtolower($file->getClientOriginalExtension());
                if (! in_array($ext, ['jpg','jpeg','png'], true)) {
                    $validator->errors()->add(
                        'image',
                        '「.png」または「.jpeg」形式でアップロードしてください。'
                    );
                }
            }
        });
    }

}
