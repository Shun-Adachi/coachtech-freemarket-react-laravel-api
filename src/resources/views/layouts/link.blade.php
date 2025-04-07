@section('link')

@if(auth()->check())
<a class="header-link__link" href="/logout">ログアウト</a>
@else
<a class="header-link__link" href="/login">ログイン</a>
@endif
<a class="header-link__link" href="/mypage">マイページ</a>
<a class="header-link__link--sell" href="/sell">出品</a>
@endsection