@extends('layouts.app')
@extends('layouts.search')
@extends('layouts.link')

@section('css')
<link rel="stylesheet" href="{{ asset('css/common/user-form.css')}}">
<link rel="stylesheet" href="{{ asset('css/auth/edit-address.css')}}">
@endsection

@section('content')
<div class="user-form">
  <!-- ヘッダー -->
  <h1 class="user-form__heading">住所の変更</h1>
  <!-- 入力フォーム -->
  <form class="user-form__form" action="/purchase/address/update" method="post">
    @method('PATCH')
    @csrf
    <input type="hidden" name="item_id" value="{{old('item_id') ?? $itemId}}" />
    <div class="user-form__group">
      <label class="user-form__label" for="shipping_post_code">郵便番号</label>
      <input class="user-form__input" type="text" name="shipping_post_code" id="shippig_post_code" value="{{old('shipping_post_code') ?? $user->shipping_post_code}}" />
      <p class="user-form__error-message">
        @error('shipping_post_code')
        {{ $message }}
        @enderror
      </p>
    </div>
    <div class="user-form__group">
      <label class="user-form__label" for="shipping_address">住所</label>
      <input class="user-form__input" type="text" name="shipping_address" id="shipping_address" value="{{old('shipping_address') ?? $user->shipping_address}}" />
      <p class="user-form__error-message">
        @error('shipping_address')
        {{ $message }}
        @enderror
      </p>
    </div>
    <div class="user-form__group">
      <label class="user-form__label" for="shipping_building">建物名</label>
      <input class="user-form__input" type="text" name="shipping_building" id="shipping_building" value="{{old('shipping_building') ?? $user->shipping_building}}" />
    </div>
    <div class="user-form__group">
      <input class="user-form__button" type="submit" value="更新する">
    </div>
  </form>
</div>
@endsection('content')