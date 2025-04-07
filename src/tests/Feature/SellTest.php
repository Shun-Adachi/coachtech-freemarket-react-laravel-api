<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Item;
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
     * 商品出品画面にて必要な情報が保存できること
     */
    public function test_item_can_be_created_with_valid_data()
    {

        $user = User::first();
        $this->actingAs($user);

        // テスト用画像のアップロード
        $imagePath = public_path('images/default-profile.png');
        $imageFile = new UploadedFile(
            $imagePath,
            'default-profile.png',
            'image/png',
            null,
            true
        );

        // ファイルシステムのモック
        Storage::fake('public');

        // 商品出品データ
        $formData = [
            'condition' => 1,
            'name' => 'テスト商品',
            'description' => 'テスト商品の説明文です。',
            'price' => 5000,
            'categories' => [1, 2],
            'image' => $imageFile,
        ];

        // 商品出品処理を実行
        $response = $this->post('/sell/create', $formData);

        // ステータスコードが302でリダイレクトされることを確認
        $response->assertStatus(302);

        // Itemsテーブルにデータが保存されていることを確認
        $this->assertDatabaseHas('items', [
            'name' => $formData['name'],
            'description' => $formData['description'],
            'user_id' => $user->id,
            'condition_id' => $formData['condition'],
            'price' => $formData['price'],
        ]);

        // 画像ファイルが正しく保存されていることを確認
        $uploadedImagePath = 'images/items/' . $imageFile->hashName();
        $this->assertTrue(Storage::disk('public')->exists($uploadedImagePath), "Image file does not exist: {$uploadedImagePath}");

        // CategoryItemテーブルにデータが保存されていることを確認
        $itemId = Item::where('name', $formData['name'])->value('id');
        foreach ($formData['categories'] as $categoryId) {
            $this->assertDatabaseHas('category_item', [
                'item_id' => $itemId,
                'category_id' => $categoryId,
            ]);
        }
    }
}
