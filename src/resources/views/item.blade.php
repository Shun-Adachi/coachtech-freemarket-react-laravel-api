@extends('layouts.app')
@extends('layouts.search')
@extends('layouts.link')

@section('css')
<link rel="stylesheet" href="{{ asset('css/item.css')}}">
@endsection

@section('content')
<div class="item-content">
  <!-- 商品画像 -->
  <img class="item__image" src="{{asset('storage/' . $item->image_path)}}" />
  <!-- 商品情報一覧 -->
  <div class="item-information">
    <!-- 商品概要 -->
    <h1 class="item-information__main-heading">{{$item->name}}</h1>
    <p class="item-information__label--brand">{{$item->user->name}}</p>
    <p class="item-information__label--price">&yen; {{$item->price}}</p>
    <div class="item-icon">
      <div class="item-icon__group">
        <a class="item-icon__link--{{$purchase ? 'inactive' : 'active'}}" href="{{'/item/favorite/' . $item->id}}">
          <img class="item-icon__image" src="/images/favorite-{{$isFavorite ? 'active' : 'inactive'}}.png" />
        </a>
        <p class="item-icon__label">{{$favoritesCount}}</p>
      </div>
      <div class=" item-icon__group">
        <img class="item-icon__image" src="/images/comment.svg" />
        <p class="item-icon__label">{{$commentsCount}}</p>
      </div>
    </div>
    @if($purchase)
    <a class="item-form__link--inactive">購入手続きへ</a>
    @else
    <a class="item-form__link--active" href="{{'/purchase/' . $item->id}}">購入手続きへ</a>
    @endif
    <!-- 説明 -->
    <h2 class="item-information__sub-heading">商品説明</h2>
    <p class="item-information__label--description">
      {!! nl2br($item->description) !!}
    </p>
    <!-- 詳細説明 -->
    <h2 class="item-information__sub-heading">商品の情報</h2>
    <div class="item-category">
      <h3 class="item-category__heading">カテゴリー</h3>
      <div class="item-category__group">
        @foreach ($itemCategories as $itemCategory)
        <p class="item-category__label">{{$itemCategory->category->name}}</p>
        @endforeach
      </div>
    </div>
    <div class="item-condition">
      <h3 class="item-condition__heading">商品の状態</h3>
      <div class="item-condition__group">
        <p class="item-condition__label">{{$item->condition->name}}</p>
      </div>
    </div>
    <!-- コメント一覧 -->
    <div class="item-comment">
      <h2 class="item-information__sub-heading">コメント({{$commentsCount}})</h2>
      @foreach ($comments as $comment)
      <div class="item-comment__user-group">
        <img
          class="item-comment__user-image"
          src="{{ $comment->user->thumbnail_path ? asset('storage/' . $comment->user->thumbnail_path) : '/images/default-profile.png'}}">
        <p class="item-comment__user-name">{{$comment->user->name}}</p>
      </div>
      <p class="item-comment__text">{!! nl2br($comment->comment) !!}</p>
      @endforeach
      @if($commentsCount === 0)
      <div class="item-comment__user-group">
        <p class="item-comment__text">この商品に関するコメントはありません</p>
      </div>
      @endif
    </div>
    <!-- コメント投稿 -->
    <form class="item-form" action="/item/comment" method="post">
      @csrf
      <h2 class="item-information__sub-heading">商品へのコメント</h2>
      <p class="item-form__error-message">
        @error('comment')
        {{ $message }}
        @enderror
      </p>
      <input type="hidden" name="item_id" value="{{$item->id}}">
      <textarea class="item-form__textarea" name="comment">{{ old('comment') }}</textarea>
      <input class="item-form__button--{{$purchase ? 'inactive' : 'active'}}" type="submit" value="コメントを送信する">
    </form>
  </div>
</div>
@endsection('content')