<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Tests\TestCase;

class SellTest extends TestCase
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
     * POST /api/sell で商品出品が正しく動作する
     *
     * @return void
     */
    public function test_item_can_be_created_with_valid_data_via_api()
    {
        // --- 前準備 ---------------------------------------------------
        // テストユーザー取得＆APIトークン認証
        $user = User::first();
        $this->actingAs($user, 'sanctum');

        // ストレージをモック
        Storage::fake('public');

        // ダミー画像ファイルを用意
        $file = UploadedFile::fake()->image('item.png');

        // 出品データ
        $payload = [
            'condition_id' => 1,
            'name'         => 'テスト商品',
            'description'  => 'テスト商品の説明文です。',
            'price'        => 5000,
            'categories'   => [1, 2],
            'image'        => $file,
        ];

        // --- APIリクエスト ---------------------------------------------
        $response = $this->postJson('/api/sell', $payload);

        // --- レスポンス検証 -------------------------------------------
        // 201 Created + メッセージ & item オブジェクトを返している
        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'item' => [
                    'id',
                    'name',
                    'image_url',
                    'price',
                    'condition_id',
                    'category_ids',
                ],
            ])
            ->assertJsonFragment([
                'name'         => $payload['name'],
                'price'        => $payload['price'],
                'condition_id' => $payload['condition_id'],
            ]);

        // JSON の category_ids には送信した categories がそのまま入っている
        $this->assertEquals(
            $payload['categories'],
            $response->json('item.category_ids')
        );

        // --- DB検証 ---------------------------------------------------
        // items テーブルにレコードが存在する
        $this->assertDatabaseHas('items', [
            'name'         => $payload['name'],
            'description'  => $payload['description'],
            'user_id'      => $user->id,
            'condition_id' => $payload['condition_id'],
            'price'        => $payload['price'],
        ]);

        // category_item テーブルに関連づけもある
        $itemId = $response->json('item.id');
        foreach ($payload['categories'] as $catId) {
            $this->assertDatabaseHas('category_item', [
                'item_id'     => $itemId,
                'category_id' => $catId,
            ]);
        }

        // --- ストレージ検証 -------------------------------------------
        // 画像が public/images/items に保存されている
        $this->assertTrue(
            Storage::disk('public')->exists("images/items/{$file->hashName()}"),
            "public ディスクに images/items/{$file->hashName()} が存在しません。"
        );
    }
}
