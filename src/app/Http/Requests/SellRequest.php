<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SellRequest extends FormRequest
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
            'name' => ['required', 'string'],
            'description' => ['required', 'string', 'max:255'],
            'categories' => ['required'],
            'condition' => ['required'],
            'price' => ['required', 'integer', 'min:50'],
        ];

        if (!$this->hasFile('image') && $this->temp_image) {
            // 一時保存されている場合は必須チェックをスキップ
            $rules['image'] = [];
        } else {
            // 通常のバリデーションルール
            $rules['image'] = ['required', 'mimes:jpg,jpeg,png'];
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'image.required' => '商品画像を選択してください',
            'image.mimes' => '画像ファイルはJPEGもしくはPNGを選択してください',
            'name.required' => '商品名を入力してください',
            'description.required' => '商品説明を入力してください',
            'description.max' => '商品説明は255以下で入力してください',
            'categories.required' => 'カテゴリーを選択してください',
            'condition.required' => '商品の状態を選択してください',
            'price.required' => '販売価格を入力してください',
            'price.integer' => '販売価格は整数を入力してください',
            'price.min' => '販売価格は50円以上で入力してください',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        handleTempImageUpload($this, $validator);
        parent::failedValidation($validator);
    }
}
