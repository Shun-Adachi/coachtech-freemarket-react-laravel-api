@extends('layouts.app')
@extends('layouts.search')
@extends('layouts.link')

@section('css')
<link rel="stylesheet" href="{{ asset('css/purchase.css')}}">
@endsection

@section('script')
<script src="https://js.stripe.com/v3/"></script>
@endsection

@section('content')
<!-- Stripe メッセージ -->
@if(session('success'))
<p style="color: green;">{{ session('success') }}</p>
@endif
@if(session('error'))
<p style="color: red;">{{ session('error') }}</p>
@endif
<div class="purchase-content">
  <form class="purchase-form" method="post">
    @csrf
    <div class="purchase-information">
      <!-- 商品情報 -->
      <div class="item-information">
        <input type="hidden" name="item_id" value="{{$item->id}}" />
        <img class="purchase-form__image" src="{{asset('storage/' . $item->image_path)}}">
        <div class="purchase-form__item-group">
          <p class="purchase-form__text--item">{{$item->name}}</p>
          <p class="purchase-form__text--item">&yen; {{$item->price}}</p>
        </div>
      </div>
      <!-- 支払方法 -->
      <div class="payment-method">
        <label class="purchase-form__label" for="payment_method">支払い方法</label>
        <select class="purchase-form__select" name="payment_method" id="payment_method">
          @foreach($paymentMethods as $payment_method)
          @if(old('payment_method'))
          <option
            value="{{$payment_method->id}}"
            {{old('payment_method') == $payment_method->id ? 'selected' : ''}}>{{$payment_method->name}}
          </option>
          @else
          <option
            value="{{$payment_method->id}}"
            {{$user->payment_method_id == $payment_method->id ? 'selected' : ''}}>{{$payment_method->name}}
          </option>
          @endif
          @endforeach
        </select>
        <p class="purchase-form__error-message">
          @error('payment_method')
          {{ $message }}
          @enderror
        </p>
      </div>
      <!-- 配送先住所 -->
      <div class="purchase-address">
        <div class="purchase-address__group">
          <label class="purchase-form__label">
            配送先
          </label>
          <div class="purchase-address__sub-group">
            <p class="purchase-form__text--address">〒 </p>
            <input class="purchase-form__input"
              type="text"
              name="shipping_post_code"
              value="{{$user->shipping_post_code}}"
              readonly />
          </div>
          <p class="purchase-form__error-message">
            @error('shipping_post_code')
            {{ $message }}
            @enderror
          </p>
          <div class="purchase-address__sub-group">
            <p class="purchase-form__text--address"></p>
            <input class="purchase-form__input"
              type="text"
              name="shipping_address"
              value="{{$user->shipping_address}}"
              readonly />
          </div>
          <p class="purchase-form__error-message">
            @error('shipping_address')
            {{ $message }}
            @enderror
          </p>
          <div class="purchase-address__sub-group">
            <p class="purchase-form__text--address"></p>
            <input class="purchase-form__input"
              type="text"
              name="shipping_building"
              value="{{$user->shipping_building}}"
              readonly />
          </div>
        </div>
        <button class="purchase-form__link" type="submit" formaction="/purchase/address">変更する</button>
      </div>
    </div>
    <!-- 支払概要 -->
    <div class="payment-summary">
      <table class="purchase-form__table">
        <tr class="purchase-form__row">
          <th class="purchase-form__cell">商品代金</th>
          <td class="purchase-form__cell">&yen; {{$item->price}}</td>
        </tr>
        <tr class=" purchase-form__row">
          <th class="purchase-form__cell">支払い方法</th>
          <td class="purchase-form__cell" name="payment_method_text">コンビニ払い</td>
        </tr>
      </table>
      <button class="purchase-form__button--{{$purchase ? 'inactive' : 'active'}}" type="submit" formaction="/purchase/checkout">購入する</button>
    </div>
  </form>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const selectBox = document.getElementsByName('payment_method')[0];
    const displayText = document.getElementsByName('payment_method_text')[0];
    if (selectBox.value === "") {
      displayText.textContent = "";
    } else {
      displayText.textContent = selectBox.options[selectBox.selectedIndex].text;
    }

    selectBox.addEventListener('change', function() {
      displayText.textContent = selectBox.options[selectBox.selectedIndex].text; // 値をテキストに反映
    });
  });
</script>

@endsection('content')