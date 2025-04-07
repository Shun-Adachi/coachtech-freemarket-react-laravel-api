<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Item;
use App\Models\Purchase;
use Tests\TestCase;

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
     * 必要な情報が取得できる（プロフィール画像、ユーザー名、出品した商品一覧、購入した商品一覧）
     */
    public function test_profile_page_displays_required_information()
    {
        $user = User::first();
        $this->actingAs($user);

        // 出品商品と購入商品を作成
        $sellItem = Item::where('user_id', $user->id)->first();
        $buyItem = Item::where('user_id', '!=', $user->id)->first();
        Purchase::create([
            'user_id' => $user->id,
            'item_id' => $buyItem->id,
            'payment_method_id' => $user->payment_method_id,
            'shipping_post_code' => $user->shipping_post_code,
            'shipping_address' => $user->shipping_address,
            'shipping_building' => $user->shipping_building,
        ]);

        // 出品していない商品と購入していない商品を取得
        $notSellItem = Item::where('user_id', '!=', $user->id)->first();
        $notPurchasedItem = Item::whereNotIn('id', function ($query) use ($user) {
            $query->select('item_id')->from('purchases')->where('user_id', $user->id);
        })->first();

        // 購入商品一覧ページの確認
        $response = $this->get('/mypage');
        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee($user->thumbnail_path);
        $response->assertSee($buyItem->name);
        $response->assertDontSee($notPurchasedItem->name);

        // 出品商品一覧ページの確認
        $response = $this->get('/mypage?tab=sell');
        $response->assertStatus(200);
        $response->assertSee($sellItem->name);
        $response->assertDontSee($notSellItem->name);
    }

    /**
     * 初期値が適切に設定されていること（プロフィール画像、ユーザー名、郵便番号、住所）
     */
    public function test_profile_edit_page_displays_correct_initial_values()
    {
        $user = User::first();
        $this->actingAs($user);
        $response = $this->get('/mypage/profile');
        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee($user->profile_image);
        $response->assertSee($user->shipping_post_code);
        $response->assertSee($user->shipping_address);
        $response->assertSee($user->shipping_buiding);
    }
}
