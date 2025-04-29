<!-- resources/views/emails/login_code.blade.php -->
<p>ログインするには以下の認証コードを入力してください。</p>
<h2>{{ $code }}</h2>
<p>このコードは {{ $expires }} まで有効です。</p>
