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
    return [
        'name'        => ['required', 'string'],
        'description' => ['required', 'string', 'max:255'],
        'categories'  => ['required', 'array'],
        'categories.*'=> ['integer', 'exists:categories,id'],
        'condition_id'   => ['required', 'integer', 'exists:conditions,id'],
        'price'       => ['required', 'integer', 'min:50'],
        'image'       => ['required', 'file', 'mimes:jpg,jpeg,png'],
    ];
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
            'condition_id.required' => '商品の状態を選択してください',
            'price.required' => '販売価格を入力してください',
            'price.integer' => '販売価格は整数を入力してください',
            'price.min' => '販売価格は50円以上で入力してください',
        ];
    }

}
