@extends('layouts.app')
@extends('layouts.search')
@extends('layouts.link')

@section('css')
<link rel="stylesheet" href="{{ asset('css/common/user-form.css')}}">
<link rel="stylesheet" href="{{ asset('css/edit-profile.css')}}">
@endsection

@section('content')
<div class="user-form">
  <h1 class="user-form__heading">プロフィール設定</h1>
  <form class="user-form__form" action="/mypage/profile/update" method="post" enctype="multipart/form-data">
    @method('PATCH')
    @csrf
    <!-- 画像選択 -->
    <div class="user-form__image-group">
      @if(session('temp_image'))
      <img class="user-form__image-preview" name="image-preview" src="{{ asset('storage/' . session('temp_image'))}}">
      @elseif($user->thumbnail_path)
      <img class="user-form__image-preview" name="image-preview" src="{{ asset('storage/' . $user->thumbnail_path)}}">
      @else
      <img class="user-form__image-preview" name="image-preview" src="{{ '/images/default-profile.png'}}">
      @endif
      <button class="user-form__file-upload-button" type="button">画像を選択する</button>
      <input
        class="user-form__hidden-file-input"
        type="file"
        name="image"
        id="image"
        accept=".jpg,.jpeg,.png">
      <input type="hidden" name="temp_image" value="{{session('temp_image') ?? ''}}">
    </div>
    <div class="user-form__group">
      <p class="user-form__error-message">
        @error('image')
        {{ $message }}
        @enderror
      </p>
    </div>
    <!-- ユーザー情報入力 -->
    <div class="user-form__group">
      <label class="user-form__label" for="name">ユーザー名</label>
      <input class="user-form__input" type="text" name="name" id="name" value="{{ old('name') ?? $user->name}}">
      <p class="user-form__error-message">
        @error('name')
        {{ $message }}
        @enderror
      </p>
    </div>
    <div class="user-form__group">
      <label class="user-form__label" for="current_post_code">郵便番号</label>
      <input class="user-form__input" type="text" name="current_post_code" id="current_post_code" value="{{ old('current_post_code') ?? $user->current_post_code}}">
      <p class="user-form__error-message">
        @error('current_post_code')
        {{ $message }}
        @enderror
      </p>
    </div>
    <div class="user-form__group">
      <label class="user-form__label" for="current_address">住所</label>
      <input class="user-form__input" type="text" name="current_address" id="current_address" value="{{ old('current_address') ?? $user->current_address}}">
      <p class=" user-form__error-message">
        @error('current_address')
        {{ $message }}
        @enderror
      </p>
    </div>
    <div class="user-form__group">
      <label class="user-form__label" for="current_building">建物名</label>
      <input class="user-form__input" type="text" name="current_building" id="current_building" value="{{ old('current_building') ?? $user->current_building}}">
    </div>
    <div class="user-form__group">

      <input type="hidden" name="id" value="{{$user->id}}">
      <input class="user-form__button" type="submit" value="更新する">
    </div>
  </form>
</div>

<!-- スクリプト -->
<script>
  const fileUploadButton = document.querySelector('.user-form__file-upload-button');
  const fileInput = document.querySelector('.user-form__hidden-file-input');
  const imagePreview = document.querySelector('.user-form__image-preview');

  fileUploadButton.addEventListener('click', () => {
    fileInput.click();
  });
  fileInput.addEventListener('change', (event) => {
    const file = event.target.files[0];

    if (!file) {
      alert('ファイルが選択されていません。');
      return;
    }

    if (!file.type.startsWith('image/')) {
      alert('画像ファイルを選択してください。');
      return;
    }

    const reader = new FileReader();

    reader.onload = (e) => {
      imagePreview.src = e.target.result;
      imagePreview.style.display = 'block';
    };

    reader.onerror = () => {
      alert('ファイルの読み込みに失敗しました。');
    };

    reader.readAsDataURL(file);
  });
</script>


@endsection('content')