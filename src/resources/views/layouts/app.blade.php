<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Coachtech-free-market</title>
  <link rel="stylesheet" href="{{ asset('css/common/sanitize.css') }}" />
  <link rel="stylesheet" href="{{ asset('css/common/header.css')}}">
  @yield('css')
  @yield('script')
</head>
<body>
  <div class="app">
    <header class="header">
      <a class="header-logo__link" href="/">
        <img class="header-logo__image" src=" /images/logo.svg" />
      </a>
      <div class="header-search">
        @yield('search')
      </div>
      <div class="header-link">
        @yield('link')
      </div>
    </header>
    <div class="content">
      @if (session('message'))
      <div class="alert__message">
        {{session('message')}}
      </div>
      @endif
      @yield('content')
    </div>
  </div>
</body>

</html>