@extends('layouts.app')
@extends('layouts.search')
@extends('layouts.link')

@section('css')
<link rel="stylesheet" href="{{ asset('css/sell.css')}}">
@endsection

@section('content')
<div class="sell-form">
  <h1 class="sell-form__main-heading">商品の出品</h1>
  <form class="sell-form__form" action="/sell/create" method="post" enctype="multipart/form-data">
    @csrf
    <!-- 商品画像 -->
    <div class="sell-form__group">
      <label class="sell-form__label" for="item-image">商品画像</label>
      <p class="sell-form__error-message">
        @error('image')
        {{ $message }}
        @enderror
      </p>
      <div class="sell-form__image-upload-container">
        <button class="sell-form__file-upload-button" type="button">画像を選択する</button>
        <input
          class="sell-form__hidden-file-input"
          type="file"
          name="image"
          accept=".jpg,.jpeg,.png">
        <img
          class="sell-form__image-preview"
          style="display: {{ session('temp_image') ? 'block' : 'none' }};"
          name="image-preview"
          src="{{ session('temp_image') ? asset('storage/' . session('temp_image')) : '' }}">
        <input type="hidden" name="temp_image" value="{{session('temp_image') ?? ''}}">
      </div>
    </div>
    <!-- 商品の詳細 -->
    <h2 class=" sell-form__sub-heading">商品の詳細</h2>

    <!-- カテゴリ -->
    <div class="sell-form__group">
      <label class="sell-form__label">カテゴリー</label>
      <p class="sell-form__error-message">
        @error('categories')
        {{ $message }}
        @enderror
      </p>
      <div class="sell-form__label-container">
        @foreach($categories as $category)
        <input
          class="sell-form__checkbox"
          type="checkbox"
          name="categories[]"
          value="{{$category->id}}"
          id="{{'checkbox' . $category->id}}"
          {{ in_array($category->id, old('categories', [])) ? 'checked' : '' }}>
        <label
          class="sell-form__category-label"
          for="{{'checkbox' . $category->id}}">
          {{$category->name}}
        </label>
        @endforeach
      </div>
    </div>
    <!-- 商品の状態 -->
    <div class="sell-form__group">
      <label class="sell-form__label" for="condition">商品の状態</label>
      <p class="sell-form__error-message">
        @error('condition')
        {{ $message }}
        @enderror
      </p>
      <select class="sell-form__select" name="condition" id="condition">
        <option value="">選択してください</option>
        @foreach($conditions as $condition)
        <option value="{{$condition->id}}" {{old('condition') == $condition->id ? 'selected' : ''}}>
          {{$condition->name}}
        </option>>
        @endforeach
      </select>
    </div>
    <!-- 商品名と説明 -->
    <h2 class="sell-form__sub-heading">商品名と説明</h2>
    <!-- 商品名 -->
    <div class="sell-form__group">
      <label class="sell-form__label" for="name">商品名</label>
      <p class="sell-form__error-message">
        @error('name')
        {{ $message }}
        @enderror
      </p>
      <input class="sell-form__input" type="text" name="name" id="name" value="{{old('name')}}">
    </div>
    <!-- 商品の説明 -->
    <div class="sell-form__group">
      <label class="sell-form__label" for="description">商品の説明</label>
      <p class="sell-form__error-message">
        @error('description')
        {{ $message }}
        @enderror
      </p>
      <textarea class=" sell-form__textarea" name="description" id="description">{{old('description')}}</textarea>
    </div>
    <!-- 販売価格 -->
    <div class="sell-form__group">
      <label class="sell-form__label" for="price">販売価格</label>
      <p class="sell-form__error-message">
        @error('price')
        {{ $message }}
        @enderror
      </p>
      <div class="sell-form__price-group">
        <label class="sell-form__label--yen-mark" for="price">&yen;</label>
        <input class="sell-form__input--price" type="number" min="0" name="price" id="price" value="{{old('price')}}">
      </div>
    </div>
    <!-- 出品ボタン -->
    <div class="sell-form__group">
      <input class="sell-form__button" type="submit" value="出品する">
    </div>
  </form>
</div>

<!-- スクリプト-->
<script>
  const fileUploadButton = document.querySelector('.sell-form__file-upload-button');
  const fileInput = document.querySelector('.sell-form__hidden-file-input');
  const imagePreview = document.querySelector('.sell-form__image-preview');

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