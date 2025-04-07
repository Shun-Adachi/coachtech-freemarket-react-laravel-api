<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Item;
use Tests\TestCase;
use Stripe\StripeClient;

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
     * 「購入する」ボタンを押下すると購入が完了する
     *
     * @return void
     */
    public function test_user_can_complete_purchase()
    {

        $user = User::first();
        $this->actingAs($user);
        $item = Item::where('user_id', '!=', $user->id)->first();

        // StripeClient のモック作成
        $stripeMock = $this->getMockBuilder(StripeClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        // sessions のモック作成
        $sessionsMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['create'])
            ->getMock();
        $sessionsMock->expects($this->once())
            ->method('create')
            ->willReturn((object) [
                'id' => 'mock_session_id',
                'url' => 'https://mock.stripe.url/checkout',
            ]);
        // checkout プロパティを直接モックとして設定
        $stripeMock->checkout = (object) [
            'sessions' => $sessionsMock,
        ];

        // モックをアプリケーションコンテナにバインド
        $this->app->instance(StripeClient::class, $stripeMock);

        // 商品購入ボタン押下
        $formData = [
            'item_id' => $item->id,
            'payment_method' => $user->payment_method_id,
            'shipping_post_code' => $user->shipping_post_code,
            'shipping_address' => $user->shipping_address,
            'shipping_building' => $user->shipping_building,
        ];
        $response = $this->post('/purchase/checkout', $formData);

        $response->assertStatus(302);
        $response->assertRedirect('https://mock.stripe.url/checkout');

        // セッションに必要な情報を保存されていることを確認
        $this->assertEquals(session('request_data')['item_id'], $formData['item_id']);
        $this->assertEquals(session('request_data')['payment_method'], $formData['payment_method']);
        $this->assertEquals(session('request_data')['shipping_post_code'], $formData['shipping_post_code']);
        $this->assertEquals(session('request_data')['shipping_address'], $formData['shipping_address']);
        $this->assertEquals(session('request_data')['shipping_building'], $formData['shipping_building']);

        // 商品購入完了処理
        $response = $this->get('/purchase/buy');

        // Assert: 購入処理が正常に完了し、リダイレクトされることを確認
        $response->assertStatus(302);
        $response->assertRedirect('/mypage');

        // 購入データがデータベースに保存されていることを確認
        $compareShippingBuilding = $formData['shipping_building'] ?: null;
        $this->assertDatabaseHas('purchases', [
            'user_id' => $user->id,
            'item_id' => $item->id,
            'payment_method_id' => $formData['payment_method'],
            'shipping_post_code' => $formData['shipping_post_code'],
            'shipping_address' => $formData['shipping_address'],
            'shipping_building' => $compareShippingBuilding,
        ]);
    }

    /**
     *  購入した商品は商品一覧画面にて「Sold」と表示される
     *
     * @return void
     */
    public function test_purchased_items_display_sold_label_with_correct_item_id()
    {

        $user = User::first();
        $this->actingAs($user);
        $item = Item::where('user_id', '!=', $user->id)->first();

        // StripeClient のモック作成
        $stripeMock = $this->getMockBuilder(StripeClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        // sessions のモック作成
        $sessionsMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['create'])
            ->getMock();
        $sessionsMock->expects($this->once())
            ->method('create')
            ->willReturn((object) [
                'id' => 'mock_session_id',
                'url' => 'https://mock.stripe.url/checkout',
            ]);
        // checkout プロパティを直接モックとして設定
        $stripeMock->checkout = (object) [
            'sessions' => $sessionsMock,
        ];

        // モックをアプリケーションコンテナにバインド
        $this->app->instance(StripeClient::class, $stripeMock);

        // 商品購入ボタン押下
        $formData = [
            'item_id' => $item->id,
            'payment_method' => $user->payment_method_id,
            'shipping_post_code' => $user->shipping_post_code,
            'shipping_address' => $user->shipping_address,
            'shipping_building' => $user->shipping_building,
        ];
        $response = $this->post('/purchase/checkout', $formData);

        // 商品購入完了処理
        $response = $this->get('/purchase/buy');

        // アイテム一覧ページへ移動
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee($item->name);
        $response->assertSee('Sold');
    }

    /**
     * 「プロフィール/購入した商品一覧」に追加されている
     *
     * @return void
     */
    public function test_purchased_item_is_added_to_profile_purchase_list()
    {

        $user = User::first();
        $this->actingAs($user);
        $item = Item::where('user_id', '!=', $user->id)->first();

        // StripeClient のモック作成
        $stripeMock = $this->getMockBuilder(StripeClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        // sessions のモック作成
        $sessionsMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['create'])
            ->getMock();
        $sessionsMock->expects($this->once())
            ->method('create')
            ->willReturn((object) [
                'id' => 'mock_session_id',
                'url' => 'https://mock.stripe.url/checkout',
            ]);
        // checkout プロパティを直接モックとして設定
        $stripeMock->checkout = (object) [
            'sessions' => $sessionsMock,
        ];

        // モックをアプリケーションコンテナにバインド
        $this->app->instance(StripeClient::class, $stripeMock);

        // 商品購入ボタン押下
        $formData = [
            'item_id' => $item->id,
            'payment_method' => $user->payment_method_id,
            'shipping_post_code' => $user->shipping_post_code,
            'shipping_address' => $user->shipping_address,
            'shipping_building' => $user->shipping_building,
        ];
        $response = $this->post('/purchase/checkout', $formData);

        // 商品購入完了処理
        $response = $this->get('/purchase/buy');

        // プロフィールページへ移動
        $response = $this->get('/mypage');
        $response->assertStatus(200);
        $viewData = $response->viewData('items');
        $this->assertNotNull($viewData, "View data 'items' is not set.");
        $this->assertTrue($viewData->contains('id', $item->id), "Purchased item is not in the 'items' list.");
        $response->assertSee($item->name);
    }

    /**
     *  送付先住所変更画面にて登録した住所が商品購入画面に反映されている
     *
     * @return void
     */
    public function test_shipping_address_is_updated_and_reflected_in_purchase_screen()
    {

        $user = User::first();
        $this->actingAs($user);
        $item = Item::where('user_id', '!=', $user->id)->first();

        // 新しい住所データ
        $formData = [
            'item_id' => $item->id,
            'shipping_post_code' => '765-4321',
            'shipping_address' => '東京都新宿区',
            'shipping_building' => '新宿ビル202',
        ];

        // 送付先住所変更処理
        $response = $this->patch('/purchase/address/update', $formData);
        $response->assertStatus(302);
        $response->assertRedirect("/purchase/{$item->id}");

        // Assert: データベースに新しい住所が保存されていることを確認
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'shipping_post_code' => $formData['shipping_post_code'],
            'shipping_address' => $formData['shipping_address'],
            'shipping_building' => $formData['shipping_building'],
        ]);

        // 商品購入画面にアクセス
        $response = $this->get("/purchase/{$item->id}");
        $response->assertStatus(200);

        // HTMLレスポンスから値を取得して確認
        $html = $response->getContent();

        // 正規表現で "shipping_post_code" value 属性を取得
        preg_match('/<input[^>]*name="shipping_post_code"[^>]*value="([^"]*)"[^>]*>/', $html, $matches);
        $this->assertNotEmpty($matches, "Input field for 'shipping_post_code' not found.");
        $this->assertEquals($formData['shipping_post_code'], $matches[1], "The value of 'shipping_post_code' does not match.");

        // 正規表現で "shipping_address" value 属性を取得
        preg_match('/<input[^>]*name="shipping_address"[^>]*value="([^"]*)"[^>]*>/', $html, $matches);
        $this->assertNotEmpty($matches, "Input field for 'shipping_address' not found.");
        $this->assertEquals($formData['shipping_address'], $matches[1], "The value of 'shipping_address' does not match.");

        // 正規表現で "shipping_building" value 属性を取得
        preg_match('/<input[^>]*name="shipping_building"[^>]*value="([^"]*)"[^>]*>/', $html, $matches);
        $this->assertNotEmpty($matches, "Input field for 'shipping_building' not found.");
        $this->assertEquals($formData['shipping_building'], $matches[1], "The value of 'shipping_building' does not match.");
    }

    /**
     *  購入した商品に送付先住所が紐づいて登録される
     *
     * @return void
     */
    public function test_shipping_address_is_linked_to_purchased_item()
    {

        $user = User::first();
        $this->actingAs($user);
        $item = Item::where('user_id', '!=', $user->id)->first();

        // 新しい住所データ
        $formData = [
            'item_id' => $item->id,
            'shipping_post_code' => '765-4321',
            'shipping_address' => '東京都新宿区',
            'shipping_building' => '新宿ビル202',
        ];

        // 送付先住所変更処理
        $response = $this->patch('/purchase/address/update', $formData);
        $response->assertStatus(302);
        $response->assertRedirect("/purchase/{$item->id}");

        // 商品購入画面にアクセス
        $response = $this->get("/purchase/{$item->id}");
        $response->assertStatus(200);

        // HTMLレスポンスから値を取得して確認
        $html = $response->getContent();

        // 正規表現で "shipping_post_code" value 属性を取得
        preg_match('/<input[^>]*name="shipping_post_code"[^>]*value="([^"]*)"[^>]*>/', $html, $matches);
        $this->assertNotEmpty($matches, "Input field for 'shipping_post_code' not found.");
        $this->assertEquals($formData['shipping_post_code'], $matches[1], "The value of 'shipping_post_code' does not match.");

        // 正規表現で "shipping_address" value 属性を取得
        preg_match('/<input[^>]*name="shipping_address"[^>]*value="([^"]*)"[^>]*>/', $html, $matches);
        $this->assertNotEmpty($matches, "Input field for 'shipping_address' not found.");
        $this->assertEquals($formData['shipping_address'], $matches[1], "The value of 'shipping_address' does not match.");

        // 正規表現で "shipping_building" value 属性を取得
        preg_match('/<input[^>]*name="shipping_building"[^>]*value="([^"]*)"[^>]*>/', $html, $matches);
        $this->assertNotEmpty($matches, "Input field for 'shipping_building' not found.");
        $this->assertEquals($formData['shipping_building'], $matches[1], "The value of 'shipping_building' does not match.");

        // StripeClient のモック作成
        $stripeMock = $this->getMockBuilder(StripeClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        // sessions のモック作成
        $sessionsMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['create'])
            ->getMock();
        $sessionsMock->expects($this->once())
            ->method('create')
            ->willReturn((object) [
                'id' => 'mock_session_id',
                'url' => 'https://mock.stripe.url/checkout',
            ]);
        // checkout プロパティを直接モックとして設定
        $stripeMock->checkout = (object) [
            'sessions' => $sessionsMock,
        ];

        // モックをアプリケーションコンテナにバインド
        $this->app->instance(StripeClient::class, $stripeMock);

        // 商品購入ボタン押下
        $formData['payment_method'] = $user->payment_method_id;
        $response = $this->post('/purchase/checkout', $formData);

        // 商品購入完了処理
        $response = $this->get('/purchase/buy');

        // プロフィールページへ移動
        $this->assertDatabaseHas('purchases', [
            'user_id' => $user->id,
            'item_id' => $item->id,
            'payment_method_id' => $formData['payment_method'],
            'shipping_post_code' => $formData['shipping_post_code'],
            'shipping_address' => $formData['shipping_address'],
            'shipping_building' => $formData['shipping_building'],
        ]);
    }
}
