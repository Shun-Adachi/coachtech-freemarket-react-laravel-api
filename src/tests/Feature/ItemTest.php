<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Models\Item;
use App\Models\User;
use App\Models\Favorite;
use App\Models\Comment;
use App\Models\CategoryItem;

class ItemTest extends TestCase
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
     * 商品詳細ページに必要な情報が表示される(複数のカテゴリーが表示されること)
     *
     * @return void
     */

    /**
     * GET /api/item/{id} が必要な情報をすべて返す
     */
    public function test_item_detail_api_returns_all_required_information()
    {
        // まずカテゴリ数が最も多いアイテムを pick
        $most = CategoryItem::select('item_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('item_id')
            ->orderByDesc('cnt')
            ->first();
        $item = Item::findOrFail($most->item_id);
        $categories = CategoryItem::with('category')
            ->where('item_id', $item->id)
            ->get()
            ->pluck('category.name')
            ->all();

        $response = $this->getJson("/api/item/{$item->id}");
        $response->assertOk()
            // JSON のキー構造
            ->assertJsonStructure([
                'id',
                'name',
                'image_url',
                'price',
                'description',
                'user' => ['id', 'name'],
                'purchase',
                'isFavorite',
                'favoritesCount',
                'commentsCount',
                'categories',
                'condition' => ['id', 'name'],
            ])
            // 基本フィールド
            ->assertJsonFragment([
                'id'          => $item->id,
                'name'        => $item->name,
                'description' => $item->description,
                'price'       => number_format($item->price),
            ])
            // condition
            ->assertJsonFragment([
                'id'   => $item->condition->id,
                'name' => $item->condition->name,
            ]);

        // categories 配列にすべて含まれていること
        $response->assertJsonPath('categories', $categories);

        // いいね数
        $favCount = Favorite::where('item_id', $item->id)->count();
        $response->assertJsonFragment(['favoritesCount' => $favCount]);

        // コメント数
        $commentCount = Comment::where('item_id', $item->id)->count();
        $response->assertJsonFragment(['commentsCount' => $commentCount]);
    }

    /**
     * POST /api/item/{id}/favorite でお気に入り登録できる
     */
    public function test_user_can_add_item_to_favorites_via_api()
    {
        // 全ユーザーを取得して……
        $users = User::all();
        $found = false;

        foreach ($users as $user) {
            // 自分の出品ではなく、自分がまだいいねしていないアイテムを探す
            $item = Item::where('user_id', '!=', $user->id)
                ->whereDoesntHave('favorites', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->first();

            if (! $item) {
                // 見つからなければ次のユーザーへ
                continue;
            }

            // ここで$item, $user が見つかったのでループを抜ける
            $found = true;
            break;
        }

        // 最後まで見つからなかったらテスト失敗
        $this->assertTrue($found, 'テスト用の対象アイテムが見つかりませんでした');

        // 見つかった$user と $item で以降のテストを行う
        $this->actingAs($user, 'sanctum');

        // いいね数カウント
        $initial = Favorite::where('item_id', $item->id)->count();

        // お気に入り登録
        $response = $this->postJson("/api/item/{$item->id}/favorite");
        $response->assertOk()
            ->assertJson(['message' => 'お気に入りに登録しました']);
        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);
        // Bearer トークンを発行して、API 認証ヘッダに付与
        $token = $user->createToken('test-token')->plainTextToken;

        // お気に入りフラグが変わったことを確認
        $this->withToken($token)
            ->getJson("/api/item/{$item->id}")
            ->assertOk()
            ->assertJsonFragment(['isFavorite'    => true])
            ->assertJsonFragment(['favoritesCount' => $initial + 1]);
    }

    /**
     * DELETE /api/item/{id}/favorite でお気に入り解除できる
     *
     * @return void
     */
    public function test_user_can_remove_favorite_from_item_via_api()
    {
        // 既にお気に入りが作成されているレコードを取得
        $favorite = Favorite::first();
        $item     = Item::findOrFail($favorite->item_id);
        $user     = User::findOrFail($favorite->user_id);

        // (2) パーソナルアクセストークンを発行
        $token = $user->createToken('test-token')->plainTextToken;

        // API で最初に商品情報を取得してお気に入り数を確認
        $initialCount = Favorite::where('item_id', $item->id)->count();

        // (4) ヘッダに Bearer トークンを付与して商品詳細を取得 → isFavorite=true, favoritesCount=初期値
        $this
            ->getJson("/api/item/{$item->id}", [
                'Authorization' => "Bearer {$token}",
            ])
            ->assertOk()
            ->assertJsonFragment([
                'isFavorite'     => true,
                'favoritesCount' => $initialCount,
            ]);

        // (5) お気に入り解除リクエスト
        $this
            ->deleteJson("/api/item/{$item->id}/favorite", [], [
                'Authorization' => "Bearer {$token}",
            ])
            ->assertOk()
            ->assertJson(['message' => 'お気に入りを解除しました']);

        // DB 上もレコードが消えていることを確認
        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        // (6) 再度商品詳細を取得 → isFavorite=false, favoritesCount=初期値−1
        $this
            ->getJson("/api/item/{$item->id}", [
                'Authorization' => "Bearer {$token}",
            ])
            ->assertOk()
            ->assertJsonFragment([
                'isFavorite'     => false,
                'favoritesCount' => $initialCount - 1,
            ]);
    }

    /**
     * POST /api/item/{id}/comments でコメントを送信できる（API版）
     *
     * @return void
     */
    public function test_logged_in_user_can_post_comment_via_api()
    {
        // ユーザー取得＆トークン発行
        $user = User::first();
        $token = $user->createToken('test-token')->plainTextToken;

        // 自分以外が出品した商品を取得
        $item = Item::where('user_id', '!=', $user->id)->first();

        // 事前のコメント数
        $initialCount = Comment::where('item_id', $item->id)->count();

        // API リクエスト
        $payload = ['comment' => 'これはテストコメントです。'];
        $response = $this->postJson(
            "/api/item/{$item->id}/comments",
            $payload,
            ['Authorization' => "Bearer {$token}"]
        );

        // ステータスと JSON 構造を検証
        $response->assertStatus(201)
            ->assertJsonStructure([
                'comment' => [
                    'id',
                    'message',        // フロント側で使っているプロパティ名
                    'createdAt',      // キャメルケース
                    'user' => [
                        'name',
                        'thumbnail_url',
                    ],
                ],
            ]);

        // DB にレコードが増えていることを確認
        $this->assertDatabaseHas('comments', [
            'item_id' => $item->id,
            'user_id' => $user->id,
            'comment' => $payload['comment'],
        ]);

        // 最終的なコメント数が +1 になっていること
        $finalCount = Comment::where('item_id', $item->id)->count();
        $this->assertEquals($initialCount + 1, $finalCount);
    }

    /**
     * POST /api/item/{itemId}/comments では未認証ユーザーはコメントを送信できない
     *
     * @return void
     */
    public function test_guest_user_cannot_post_comment_via_api()
    {
        // シーディング済みの最初の商品を取得
        $item = Item::first();
        $initialCount = Comment::where('item_id', $item->id)->count();

        // API に対して JSON リクエスト（認証なし）
        $response = $this->postJson("/api/item/{$item->id}/comments", [
            'comment' => 'これはテストコメントです。',
        ]);

        // 401 Unauthorized を返し、コメントは追加されない
        $response->assertStatus(401);

        $this->assertEquals(
            $initialCount,
            Comment::where('item_id', $item->id)->count(),
            '未認証ユーザーのコメントは登録されていないはず'
        );
    }

    /**
     * POST /api/item/{itemId}/comments ではコメントが空の場合にバリデーションエラーを返す
     *
     * @return void
     */
    public function test_validation_error_returned_when_comment_is_empty_via_api()
    {
        // シーディング済みの最初のユーザーで認証
        $user = User::first();
        $this->actingAs($user, 'sanctum');

        // 自分が出品していないアイテムを取得
        $item = Item::where('user_id', '!=', $user->id)->first();

        // 空コメントで API リクエスト
        $response = $this->postJson("/api/item/{$item->id}/comments", [
            'comment' => '',
        ]);

        // 422 と JSON のバリデーションエラーを確認
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['comment']);
    }

    /**
     * POST /api/item/{itemId}/comments では255文字超過でバリデーションエラーを返す
     *
     * @return void
     */
    public function test_validation_error_returned_when_comment_exceeds_max_length_via_api()
    {
        // 認証ユーザーを用意
        $user = User::first();
        $this->actingAs($user, 'sanctum');

        // 他人の商品をピックアップ
        $item = Item::where('user_id', '!=', $user->id)->first();

        // 256文字のコメントを作成
        $longComment = str_repeat('a', 256);

        // API へ JSON リクエスト
        $response = $this->postJson("/api/item/{$item->id}/comments", [
            'comment' => $longComment,
        ]);

        // 422 とバリデーションエラーを検証
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['comment']);
    }
}
