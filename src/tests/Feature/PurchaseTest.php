<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Item;
use App\Models\Purchase;
use Tests\TestCase;

class PurchaseTest extends TestCase
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
     * POST /api/purchase/{item}/checkout でセッション ID が返ってくる
     */
    public function test_can_create_checkout_session_via_api()
    {
        $user = User::first();
        $item = Item::where('user_id', '!=', $user->id)->first();
        $this->actingAs($user, 'sanctum');

        // モックせずに本物の Stripe client を使う場合は
        // 必要ならここで HTTP クライアントをモック。
        $res = $this->postJson("/api/purchase/{$item->id}/checkout", [
            'item_id'            => $item->id,
            'payment_method'     => $user->payment_method_id,
            'shipping_post_code' => $user->shipping_post_code,
            'shipping_address'   => $user->shipping_address,
            'shipping_building'  => $user->shipping_building,
        ]);

        $res->assertStatus(200)
            ->assertJsonStructure(['id']);

        $this->assertIsString($res->json('id'));
    }

    /**
     * POST /api/purchase で購入完了し DB と JSON が更新される
     */
    public function test_user_can_complete_purchase_via_api()
    {
        $user = User::first();
        $item = Item::where('user_id', '!=', $user->id)->first();
        $this->actingAs($user, 'sanctum');

        // 購入確定
        $res = $this->postJson('/api/purchase', [
            'item_id'           => $item->id,
            'payment_method'    => $user->payment_method_id,
            'shipping_post_code' => $user->shipping_post_code,
            'shipping_address'  => $user->shipping_address,
            'shipping_building' => $user->shipping_building,
        ]);

        $res->assertStatus(201)
            ->assertJson([
                'message' => '購入が確定しました。',
            ]);

        // DB にレコードがあること
        $this->assertDatabaseHas('purchases', [
            'user_id'           => $user->id,
            'item_id'           => $item->id,
            'payment_method_id' => $user->payment_method_id,
            'shipping_post_code' => $user->shipping_post_code,
            'shipping_address'  => $user->shipping_address,
            'shipping_building' => $user->shipping_building ?: null,
        ]);
    }

    /**
     * 購入した商品は商品一覧 API で is_sold = true として返ってくる
     *
     * @return void
     */
    public function test_purchased_items_display_sold_label_with_correct_item_id_via_api()
    {
        // 1) テスト用ユーザ―と商品を用意
        $user = User::first();
        $item = Item::where('user_id', '!=', $user->id)->first();

        // 2) 購入データを作成
        Purchase::create([
            'user_id'             => $user->id,
            'item_id'             => $item->id,
            'payment_method_id'   => 1,
            'shipping_post_code'  => '123-4567',
            'shipping_address'    => '東京都千代田区1-1-1',
            'shipping_building'   => 'テストビル',
        ]);

        // 3) API による一覧取得（認証付き）
        $this->actingAs($user, 'sanctum');
        $res = $this->getJson('/api/items');

        // 4) ステータスと JSON 構造を検証
        $res->assertOk()
            ->assertJsonStructure([
                ['id', 'name', 'image_url', 'is_sold', 'isFavorite'],
            ])
            // 5) 購入済アイテムの is_sold が true になっていることを確認
            ->assertJsonFragment([
                'id'      => $item->id,
                'is_sold' => true,
            ]);
    }

    /**
     * /api/mypage に購入済みアイテムが含まれている
     *
     * @return void
     */
    public function test_purchased_item_is_added_to_profile_purchase_list_via_api()
    {
        // 1) テスト用ユーザーと他ユーザーを作成
        // 2) 他ユーザー出品のアイテムを作成
        $user = User::first();
        $item = Item::where('user_id', '!=', $user->id)->first();

        // 3) 購入データを直接作成
        Purchase::create([
            'user_id'             => $user->id,
            'item_id'             => $item->id,
            'payment_method_id'   => 1,
            'shipping_post_code'  => '123-4567',
            'shipping_address'    => '東京都千代田区1-1-1',
            'shipping_building'   => 'テストビル',
        ]);

        // 4) Sanctum 認証をセットして API コール
        $this->actingAs($user, 'sanctum');
        $response = $this->getJson('/api/mypage');

        // 5) ステータスと JSON 構造を検証
        $response->assertOk()
            ->assertJsonStructure([
                'user'                   => ['id', 'name', 'thumbnail_url'],
                'sellingItems',
                'purchasedItems',
                'tradingItems',
                'totalTradePartnerMessages',
                'averageTradeRating',
                'ratingCount',
            ])
            // 6) purchasedItems 配列内に対象アイテムの情報が含まれていることをチェック
            ->assertJsonFragment([
                'id'       => $item->id,
                'name'     => $item->name,
                'is_sold'  => true,
            ]);
    }

    /**
     * PUT /api/purchase/address/{item_id} で送付先住所を更新し、
     * GET /api/purchase/{item_id} で反映を確認できる
     *
     * @return void
     */
    public function test_shipping_address_is_updated_and_reflected_in_purchase_screen_via_api()
    {
        // 1) テストユーザーと別ユーザー出品アイテムを用意
        $user = User::first();
        $item = Item::where('user_id', '!=', $user->id)->first();

        // 2) Sanctum 認証をセット
        $this->actingAs($user, 'sanctum');

        // 3) 更新用の新しい住所データ
        $payload = [
            'shipping_post_code' => '765-4321',
            'shipping_address'   => '東京都新宿区',
            'shipping_building'  => '新宿ビル202',
        ];

        // 4) API で PUT リクエストして更新を実行
        $response = $this->putJson("/api/purchase/address/{$item->id}", $payload);

        // 5) ステータス 200 と、更新後のフィールドを JSON で返す想定
        $response->assertOk()
            ->assertJsonFragment([
                'shipping_post_code' => $payload['shipping_post_code'],
                'shipping_address'   => $payload['shipping_address'],
                'shipping_building'  => $payload['shipping_building'],
            ]);

        // 6) データベースにも正しく保存されていることを確認
        $this->assertDatabaseHas('users', [
            'id'                 => $user->id,
            'shipping_post_code' => $payload['shipping_post_code'],
            'shipping_address'   => $payload['shipping_address'],
            'shipping_building'  => $payload['shipping_building'],
        ]);

        // 7) 続けて購入用 API を叩き、更新情報が反映されているか確認
        $purchaseInfo = $this->getJson("/api/purchase/{$item->id}")
            ->assertOk()
            ->json();

        $this->assertEquals(
            $payload['shipping_post_code'],
            $purchaseInfo['shippingDefaults']['shipping_post_code']
        );
        $this->assertEquals(
            $payload['shipping_address'],
            $purchaseInfo['shippingDefaults']['shipping_address']
        );
        $this->assertEquals(
            $payload['shipping_building'],
            $purchaseInfo['shippingDefaults']['shipping_building']
        );
    }

    /**
     * 購入した商品に送付先住所が紐づいて登録される（API版）
     *
     * @return void
     */
    public function test_shipping_address_is_linked_to_purchased_item_via_api()
    {
        // ── ① テストユーザー＆別ユーザーのアイテムを取得 ──
        $user = User::first();
        $item = Item::where('user_id', '!=', $user->id)->first();

        // ── ② Sanctum 認証をセット ──
        $this->actingAs($user, 'sanctum');

        // ── ③ 送付先住所の更新データ ──
        $payload = [
            'shipping_post_code'  => '765-4321',
            'shipping_address'    => '東京都新宿区',
            'shipping_building'   => '新宿ビル202',
        ];

        // ── ④ API で配送先更新 ──
        $this->putJson("/api/purchase/address/{$item->id}", $payload)
            ->assertOk()
            ->assertJson([
                'message'          => '配送先住所を変更しました',
                'shippingDefaults' => $payload,
            ]);

        // ── ⑤ DB も更新されていることを確認 ──
        $this->assertDatabaseHas('users', array_merge(
            ['id' => $user->id],
            $payload
        ));

        // ── ⑥ 購入確定 API 用のペイロードに決済方法を追加 ──
        $purchaseData = $payload + [
            'item_id'        => $item->id,
            'payment_method' => $user->payment_method_id,
        ];

        // ── ⑦ POST /api/purchase で購入を確定 ──
        $this->postJson('/api/purchase', $purchaseData)
            ->assertStatus(201)
            ->assertJson(['message' => '購入が確定しました。']);

        // ── ⑧ purchases テーブルに正しくレコードがあることを確認 ──
        $this->assertDatabaseHas('purchases', [
            'user_id'            => $user->id,
            'item_id'            => $item->id,
            'payment_method_id'  => $purchaseData['payment_method'],
            'shipping_post_code' => $purchaseData['shipping_post_code'],
            'shipping_address'   => $purchaseData['shipping_address'],
            'shipping_building'  => $purchaseData['shipping_building'],
        ]);
    }
}
