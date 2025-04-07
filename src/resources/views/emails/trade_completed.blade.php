@component('mail::message')
# 【coachtechフリマ】取引完了のお知らせ

{{ $trade->purchase->item->user->name }} 様

{{ $trade->purchase->user->name }} さんとの取引が完了致しました。<br>
詳細は以下のリンクよりご確認ください。

@component('mail::button', ['url' => url('/trades/' . $trade->id . '/messages')])
取引の詳細を見る
@endcomponent

<br>
{{ config('app.name') }}
@endcomponent