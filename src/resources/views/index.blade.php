@extends('layouts.app')
@extends('layouts.search')
@extends('layouts.link')

@section('css')
<link rel="stylesheet" href="{{ asset('css/common/item-list.css')}}">
<link rel="stylesheet" href="{{ asset('css/index.css')}}">
@endsection

@section('content')
<!-- アイテムタブ -->
<div class="item-tab">
  <a class="{{$tab === 'mylist' ? 'item-tab__link' : 'item-tab__link--active'}}" href="/">おすすめ</a>
  <a class="{{$tab === 'mylist' ? 'item-tab__link--active' : 'item-tab__link'}}" href="/?tab=mylist">マイリスト</a>
</div>

<!-- アイテムリスト -->
<div class="item-list">
  @foreach ($items as $item)
  <div class="item-card">
    <a class="item-card__link" href="{{'/item/' . $item->id}}">
      @if (in_array($item->id, $soldItemIds))
      <span class="item-card__text--sold">Sold</span>
      @endif
      <img class="item-card__image" src="{{asset('storage/' . $item->image_path)}}" />
    </a>
    <p class="item-card__label">{{$item->name}}</p>
  </div>
  @endforeach
</div>

</div>
@endsection('content')