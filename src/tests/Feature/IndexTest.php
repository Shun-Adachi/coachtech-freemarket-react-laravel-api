<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Item;
use App\Models\User;
use App\Models\Purchase;
use App\Models\Favorite;

class IndexTest extends TestCase
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
     * 全商品を取得できる
     *
     * @return void
     */
    public function test_all_items_are_displayed()
    {
        $items = Item::get();
        $response = $this->get('/');
        $response->assertStatus(200);
        foreach ($items as $item) {
            $response->assertSee($item->name);
        }
    }

    /**
     * 購入済み商品は「Sold」と表示される
     *
     * @return void
     */
    public function test_purchased_items_display_sold_label()
    {
        $user = User::first();
        $item = Item::where('user_id', '!=', $user->id)->first();
        // 購入済み商品のダミーデータを作成
        Purchase::create([
            'item_id' => $item->id,
            'user_id' => $user->id,
            'payment_method_id' => $user->payment_method_id,
            'shipping_post_code' => $user->shipping_post_code,
            'shipping_address' => $user->shipping_address,
            'shipping_building' => $user->shipping_building,
        ]);
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Sold');
    }

    /**
     * 自分が出品した商品は表示されない
     *
     * @return void
     */
    public function test_items_created_by_user_are_not_displayed()
    {
        $user = User::first();
        $this->actingAs($user);
        $response = $this->get('/');
        $items = Item::all();
        foreach ($items as $item) {
            if ($item->user_id === $user->id) {
                $response->assertDontSee($item->name); // 自分の商品は表示されない
            } else {
                $response->assertSee($item->name); // 他のユーザーの商品は表示される
            }
        }
    }

    /**
     * マイリストで購入済み商品は「Sold」と表示される
     *
     * @return void
     */
    public function test_purchased_items_display_sold_label_in_mylyst()
    {
        $user = User::first();
        $this->actingAs($user);
        // いいねと商品情報を取得
        $favorites = Favorite::where('user_id', $user->id)->pluck('item_id');
        $item = Item::whereIn('id', $favorites)->first();
        // 購入済み商品のダミーデータを作成
        Purchase::create([
            'item_id' => $item->id,
            'user_id' => $user->id,
            'payment_method_id' => $user->payment_method_id,
            'shipping_post_code' => $user->shipping_post_code,
            'shipping_address' => $user->shipping_address,
            'shipping_building' => $user->shipping_building,
        ]);
        $response = $this->get('/?tab=mylist');
        $response->assertStatus(200);
        $response->assertSee('Sold');
    }

    /**
     * マイリストでいいねした商品だけが表示される
     *
     * @return void
     */
    public function test_only_favorited_items_are_displayed()
    {
        $user = User::first();
        $this->actingAs($user);
        $response = $this->get('/?tab=mylist');
        $response->assertStatus(200);

        // いいねと商品情報を取得
        $favorites = Favorite::where('user_id', $user->id)->pluck('item_id');
        $items = Item::whereIn('id', $favorites)->get();

        // いいねした商品名が表示されることを確認
        foreach ($items as $item) {
            $response->assertSee($item->name);
        }

        // いいねしていない商品名は表示されない
        $nonFavoriteItems = Item::whereNotIn('id', $favorites)->get();
        foreach ($nonFavoriteItems as $item) {
            $response->assertDontSee($item->name);
        }
    }

    /**
     * マイリストで自分が出品した商品は表示されない
     *
     * @return void
     */
    public function test_my_list_does_not_show_my_items()
    {
        $user = User::first();
        $this->actingAs($user);
        $response = $this->get('/?tab=mylist');
        $response->assertStatus(200);

        // いいねした商品のみを取得
        $favoritedItems = Favorite::where('user_id', $user->id)->pluck('item_id');
        $items = Item::whereIn('id', $favoritedItems)->get();

        // 自分が出品していないいいねされた商品の表示を確認
        foreach ($items as $item) {
            if ($item->user_id === $user->id) {
                $response->assertDontSee($item->name); // 自分の商品は表示されない
            } else {
                $response->assertSee($item->name); // 他のユーザーの商品は表示される
            }
        }
    }

    /**
     * マイリストで未認証の場合は何も表示されない
     *
     * @return void
     */
    public function test_my_list_shows_nothing_when_unauthenticated()
    {
        $response = $this->get('/?tab=mylist');
        $response->assertStatus(200);
        $items = Item::all();
        foreach ($items as $item) {
            $response->assertDontSee($item->name);
        }
    }

    /**
     * 商品名で部分一致検索ができる
     *
     * @return void
     */
    public function test_search_by_partial_item_name()
    {
        $user = User::first();
        $this->actingAs($user);
        $keyword = 'ー';
        $response = $this->post('/', ['keyword' => $keyword]);
        $response->assertStatus(200);

        // 検索結果に部分一致する自身が出品していない商品が表示されることを確認
        $matchingItems = Item::where('user_id', '!=', $user->id)
            ->where('name', 'LIKE', "%{$keyword}%")
            ->get();

        // 自身が出品していない部分一致する商品名が表示される
        foreach ($matchingItems as $item) {
            $response->assertSee($item->name);
        }

        // 自身が出品している、もしくは部分一致していない商品名が表示される
        $nonMatchingItems = Item::where('name', 'NOT LIKE', "%{$keyword}%")->get();
        foreach ($nonMatchingItems as $item) {
            $response->assertDontSee($item->name);
        }
    }

    /**
     * 検索状態がマイリストでも保持されている
     *
     * @return void
     */
    public function test_search_state_is_retained_in_my_list()
    {
        $user = User::first();
        $this->actingAs($user);

        //いいねした商品からキーワードを取得
        $favoritedItem = Favorite::with('item')->where('user_id', $user->id)->first();
        $keyword = $favoritedItem ? $favoritedItem->item->name : '';

        $this->post('/', [
            'keyword' => $keyword,
            'tab' => '',
        ]);
        $this->assertEquals($keyword, session('keyword'));

        //マイリストへ移動
        $response = $this->get('/?tab=mylist');
        $response->assertStatus(200);
        $response->assertSee($keyword);

        // Assert: マイリストに表示される商品が検索キーワードに一致していることを確認
        $favoritedItems = Favorite::where('user_id', $user->id)->pluck('item_id');
        $matchingItems = Item::whereIn('id', $favoritedItems)
            ->where('name', 'LIKE', "%{$keyword}%")
            ->get();
        foreach ($matchingItems as $item) {
            $response->assertSee($item->name);
        }

        // Assert: マイリストに表示されない商品が確認できないこと
        $nonMatchingItems = Item::whereIn('id', $favoritedItems)
            ->where('name', 'NOT LIKE', "%{$keyword}%")
            ->get();
        foreach ($nonMatchingItems as $item) {
            $response->assertDontSee($item->name);
        }
    }
}
