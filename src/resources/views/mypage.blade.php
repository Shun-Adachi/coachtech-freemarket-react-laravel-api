@extends('layouts.app')
@extends('layouts.search')
@extends('layouts.link')

@section('css')
<link rel="stylesheet" href="{{ asset('css/common/item-list.css')}}">
<link rel="stylesheet" href="{{ asset('css/mypage.css')}}">
@endsection

@section('content')
<!-- ユーザー情報 -->
<div class="profile__group">
  <img
    class="profile__image"
    src="{{$user->thumbnail_path ? asset('storage/' . $user->thumbnail_path) : '/images/default-profile.png' }}">
  <div class="profile__group--detail">
    <p class="profile__label" type="submit">{{$user->name}}</p>
    <div class="profile__group--rating">
      @if($ratingCount)
        @for($i=1; $i <= 5; $i++ )
        <img
          class="profile__rating"
          src="/images/Star-{{$averageTradeRating >= $i ? 'active' : 'inactive'}}.svg">
        @endfor
      @endif
    </div>
  </div>
  <a class="profile__link" href="/mypage/profile">プロフィールを編集 </a>
</div>
<!-- アイテムタブ -->
<div class="item-tab">
  <a class="{{!$tab ? 'item-tab__link--active' : 'item-tab__link'}}" href="/mypage">購入した商品</a>
  <a class="{{$tab === 'sell' ? 'item-tab__link--active' : 'item-tab__link'}}" href="/mypage?tab=sell">出品した商品</a>
  <a class="{{$tab === 'trade' ? 'item-tab__link--active' : 'item-tab__link'}}" href="/mypage?tab=trade">取引中の商品
    @if($totalTradePartnerMessages)
      <span class="item-tab__message-count">{{$totalTradePartnerMessages}}</span>
    @endif
  </a>
</div>
<!-- アイテムリスト -->
<div class="item-list">
  @foreach ($items as $item)
  <div class="item-card">
    @if($tab === 'sell')
      <img class="item-card__image" src="{{asset('storage/' . $item->image_path)}}" />
    @elseif($tab === 'trade')
      <a class="item-card__link" href="{{'/trades/' . $item->trade_id . '/messages'}}">
        <!-- 未読メッセージ数のバッジを表示 -->
        @if($item->message_count)
          <div class="item-card__unread-badge">{{ $item->message_count }}</div>
        @endif
        <img class="item-card__image" src="{{asset('storage/' . $item->image_path)}}" />
      </a>
    @else
      <a class="item-card__link" href="{{'/item/' . $item->id}}">
        <img class="item-card__image" src="{{asset('storage/' . $item->image_path)}}" />
      </a>
    @endif
    <p class="item-card__label">{{$item->name}}</p>
  </div>
  @endforeach
</div>
@endsection('content')