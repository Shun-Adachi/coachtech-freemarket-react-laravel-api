<body>
  <p>いつもCoachtechフリマをご利用いただき、誠にありがとうございます。</br></br>
    現在、ログイン操作が行われました。</br>
    この操作がご本人によるものであれば、以下のリンクからログインしてください。</p>
  <!-- SPA のログイン画面に token クエリを渡す -->
  <a
    href="{{ config('app.frontend_url') . '/login?token=' . $token }}"
    target="_self"
  >
    ログインする
  </a>

</body>
<footer>
  <img
    src="{{ asset('images/logo.svg') }}" alt="Logo"
    style="max-width: 300px; height: auto; margin-top: 40px;">
</footer>