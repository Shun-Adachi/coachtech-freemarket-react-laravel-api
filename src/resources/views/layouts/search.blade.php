@section('search')
<form class="header-search-form__form" action="/" method="post">
  @csrf
  <input type="hidden" name="tab" value="{{$tab ?? ''}}">
  <input class="header-search-form__input" type="text" placeholder="なにをお探しですか？" name="keyword" value="{{session('keyword') ?? ''}}">
</form>
@endsection