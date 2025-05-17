<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseRequest extends FormRequest
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
            'item_id'        => ['required', 'exists:items,id'],
            'shipping_post_code'  => ['required', 'regex:/^\d{3}-\d{4}$/'],
            'shipping_address'  => ['required', 'string', 'max:255'],
            'shipping_building' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['required', 'in:1,2'],
        ];
    }

    public function messages()
    {
        return [
            'item_id.required'   => '商品が指定されていません',
            'item_id.exists'     => '指定された商品が存在しません',
            'shipping_post_code.*'    => '郵便番号は 123-4567 形式で入力してください',
            'shipping_address.*'    => '住所を入力してください',
            'payment_method.*'   => '支払方法を選択してください',
        ];
    }
}
