<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Item;
use App\Models\User;
use App\Models\Purchase;

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
        $response = $this->getJson('/api/items');
        $response->assertOk()
                 ->assertJsonCount($items->count());
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
        $response = $this->actingAs($user)->getJson('/api/items');
        $response->assertOk()
                 ->assertJsonFragment([
                     'id'      => $item->id,
                     'is_sold' => true,
                 ]);
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
        $own = Item::where('user_id', $user->id)->first();
        $other = Item::where('user_id', '!=', $user->id)->first();

        $response = $this->getJson('/api/items');
        $response->assertOk()
                 ->assertJsonMissing(['id' => $own->id])
                 ->assertJsonFragment(['id' => $other->id]);
    }

    /**
     * keyword=キーワード で部分一致検索ができる
     */
    public function test_search_by_partial_item_name()
    {
        $user = User::first();
        $this->actingAs($user);

        // 名前に「ー」を含む、自分以外のアイテム
        $keyword = 'ー';
        $matching = Item::where('user_id', '!=', $user->id)
                        ->where('name', 'LIKE', "%{$keyword}%")
                        ->pluck('id')
                        ->all();
        $nonMatching = Item::where('name', 'NOT LIKE', "%{$keyword}%")
                           ->pluck('id')
                           ->all();

        $response = $this->getJson("/api/items?keyword={$keyword}");
        $response->assertOk();

        foreach ($matching as $id) {
            $response->assertJsonFragment(['id' => $id]);
        }
        foreach ($nonMatching as $id) {
            $response->assertJsonMissing(['id' => $id]);
        }
    }
}
