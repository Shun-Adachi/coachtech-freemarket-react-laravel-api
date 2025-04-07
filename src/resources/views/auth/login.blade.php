@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/common/user-form.css')}}">
<link rel="stylesheet" href="{{ asset('css/auth/login.css')}}">
@endsection

@section('content')
<div class="user-form">
  <h2 class="user-form__heading">ログイン</h2>
  <form class="user-form__form" action="/login" method="post">
    @csrf
    <div class="user-form__group">
      <label class="user-form__label" for="email">メールアドレス</label>
      <input class="user-form__input" type="text" name="email" id="email" value="{{ old('email') }}">
      <p class="user-form__error-message">
        @error('email')
        {{ $message }}
        @enderror
      </p>
    </div>
    <div class="user-form__group">
      <label class="user-form__label" for="password">パスワード</label>
      <input class="user-form__input" type="password" name="password" id="password">
      <p class="user-form__error-message">
        @error('password')
        {{ $message }}
        @enderror
      </p>
    </div>
    <div class="user-form__group">
      <input class="user-form__button" type="submit" value="ログインする">
    </div>
    <a class="user-form__link" href="/register">会員登録はこちら</a>
  </form>
</div>
@endsection('content')