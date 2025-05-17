<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Item;
use App\Models\Purchase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class MypageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * シーディングを有効にするための設定
     *
     * @return bool
     */
    protected function shouldSeed()
    {
        return true;
    }

    /**
     * GET /api/mypage でプロフィールに必要な情報が返る
     */
    public function test_profile_api_returns_required_information()
    {
        // 1) テスト用ユーザー取得＆Sanctum 認証
        $user = User::first();
        $this->actingAs($user, 'sanctum');

        // ——————————————————————————————————————————————
        // ご提示のスニペットはそのまま利用
        // 出品商品と購入商品を作成
        $sellItem = Item::where('user_id', $user->id)->first();
        $buyItem  = Item::where('user_id', '!=', $user->id)->first();
        Purchase::create([
            'user_id'             => $user->id,
            'item_id'             => $buyItem->id,
            'payment_method_id'   => $user->payment_method_id,
            'shipping_post_code'  => $user->shipping_post_code,
            'shipping_address'    => $user->shipping_address,
            'shipping_building'   => $user->shipping_building,
        ]);

        $notPurchasedItem = Item::whereNotIn('id', function ($q) use ($user) {
            $q->select('item_id')->from('purchases')->where('user_id', $user->id);
        })->first();

        $notSellItem = Item::where('user_id', '!=', $user->id)->first();

        // 1 回の API コール
        $response = $this->getJson('/api/mypage')
            ->assertOk()
            ->assertJsonStructure([
                'user'                      => ['id', 'name', 'thumbnail_url'],
                'sellingItems',
                'purchasedItems',
                'tradingItems',
                'totalTradePartnerMessages',
                'averageTradeRating',
                'ratingCount',
            ]);

        // JSON をデコードしてサブ配列だけ検証
        $data = $response->json();

        // ■ user 情報
        $this->assertEquals($user->id,   $data['user']['id']);
        $this->assertEquals($user->name, $data['user']['name']);

        // ■ purchasedItems の ID リスト
        $purchasedIds = array_column($data['purchasedItems'], 'id');
        $this->assertContains($buyItem->id, $purchasedIds, '購入アイテムが含まれること');
        $this->assertNotContains($notPurchasedItem->id, $purchasedIds, '購入していないアイテムは含まれないこと');

        // ■ sellingItems の ID リスト
        $sellingIds = array_column($data['sellingItems'], 'id');
        $this->assertContains($sellItem->id, $sellingIds, '出品アイテムが含まれること');
        $this->assertNotContains($notSellItem->id, $sellingIds, '出品していないアイテムは含まれないこと');
    }


    /**
     * 認証済みユーザーの基本情報（編集画面の初期値）が正しく返ってくる
     *
     * @return void
     */
    public function test_profile_edit_api_returns_correct_initial_values()
    {
        // 1) テスト用ユーザー取得＆ Sanctum 認証
        $user = User::first();
        Sanctum::actingAs($user, [], 'sanctum');

        // 2) API 呼び出し
        $response = $this->getJson('/api/mypage/profile');

        // 3) ステータスと JSON 構造を検証
        $response->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
                'thumbnail_url',
                'current_post_code',
                'current_address',
                'current_building',
            ])
            ->assertJson([
                'id'                => $user->id,
                'name'              => $user->name,
                // thumbnail_url は storage URL か null
                'thumbnail_url'     => $user->thumbnail_path
                    ? asset('storage/' . $user->thumbnail_path)
                    : null,
                'current_post_code' => $user->current_post_code,
                'current_address'   => $user->current_address,
                'current_building'  => $user->current_building,
            ]);
    }

    /**
     * 未認証の場合は 401 が返ってくる
     *
     * @return void
     */
    public function test_profile_edit_api_requires_authentication()
    {
        $this->getJson('/api/mypage/profile')
            ->assertUnauthorized(); // 401
    }
}
