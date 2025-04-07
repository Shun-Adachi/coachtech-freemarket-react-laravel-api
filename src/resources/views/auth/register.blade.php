@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/common/user-form.css')}}">
<link rel="stylesheet" href="{{ asset('css/auth/register.css')}}">
@endsection

@section('content')
<div class="user-form">
  <h2 class="user-form__heading">会員登録</h2>
  <form class="user-form__form" action="/register" method="post">
    @csrf
    <div class="user-form__group">
      <label class="user-form__label" for="name">ユーザー名</label>
      <input class="user-form__input" type="text" name="name" id="name" value="{{ old('name') }}">
      <p class="user-form__error-message">
        @error('name')
        {{ $message }}
        @enderror
      </p>
    </div>
    <div class="user-form__group">
      <label class="user-form__label" for="email">メールアドレス</label>
      <input class="user-form__input" type="email" name="email" id="email" value="{{ old('email') }}">
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
      <label class="user-form__label" for="password_confirmation">確認用パスワード</label>
      <input class="user-form__input" type="password" name="password_confirmation" id="password_confirmation">
      <p class="user-form__error-message">
      </p>
    </div>
    <div class="user-form__group">
      <input class="user-form__button" type="submit" value="登録する">
    </div>
    <a class="user-form__link" href="/login">ログインはこちら</a>
  </form>
</div>
@endsection('content')