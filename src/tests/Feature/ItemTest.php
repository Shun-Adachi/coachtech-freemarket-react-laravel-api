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

    public function test_item_detail_page_displays_all_required_information()
    {
        // 複数カテゴリを持つ商品データを取得
        $mostFrequentItem = CategoryItem::select('item_id', DB::raw('COUNT(*) as count'))
            ->groupBy('item_id')
            ->orderByDesc('count')
            ->first();
        $item = Item::where('id', $mostFrequentItem->item_id)->first();
        $categoryItems = CategoryItem::with('category')->where('item_id', $item->id)->get();

        // 商品詳細ページ表示
        $response = $this->get("/item/{$item->id}");
        $response->assertStatus(200);

        // 商品詳細ページに必要な情報が表示されていることを確認
        $response->assertSee($item->name);
        $response->assertSee($item->user->id);
        $response->assertSee(number_format($item->price));
        $response->assertSee($item->description);
        $response->assertSee($item->condition->name);

        // 関連するすべてのカテゴリ名が表示されていることを確認
        foreach ($categoryItems as $categoryItem) {
            $response->assertSee($categoryItem->category->name);
        }

        // 商品画像が表示されていることを確認
        $response->assertSee($item->image_url);

        // いいね数が表示されていることを確認
        $favoritesCount = Favorite::where('item_id', $item->id)->count();
        $response->assertSee((string)$favoritesCount);

        // コメント情報が表示されていることを確認
        $comments = Comment::where('item_id', $item->id)->get();
        foreach ($comments as $comment) {
            $response->assertSee($comment->content);
            $response->assertSee($comment->user->name);
        }
    }

    /**
     * いいねアイコンを押下することで商品をいいね登録できる
     *
     * @return void
     */
    public function test_user_can_add_item_to_favorites()
    {
        // 全商品のコレクションを取得
        $items = Item::all();
        $users = User::all();
        // お気に入り登録にない商品とユーザーの組み合わせを取得
        foreach ($items as $item) {
            foreach ($users as $user) {
                if ($item->user_id !== $user->id) {
                    $noFavoritesByOthers  = Favorite::where('item_id', $item->id)
                        ->where('user_id', $user->id)
                        ->doesntExist();

                    // 出品者以外のいいねがついていない場合、この商品を返す
                    if ($noFavoritesByOthers) {
                        $tempItem = $item;
                        $tempUser = $user;
                        break;
                    }
                }
            }
            if ($noFavoritesByOthers) {
                break;
            }
        }
        $item = $tempItem;
        $user = $tempUser;
        $this->actingAs($user);

        // 商品詳細画面表示
        $this->get("/item/{$item->id}");
        $tempFavoritesCount = Favorite::where('item_id', $item->id)->count();

        // いいねボタン押下
        $response = $this->get("/item/favorite/{$item->id}");
        $response->assertStatus(302);
        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);
        $favoritesCount = Favorite::where('item_id', $item->id)->count();

        $this->assertEquals($tempFavoritesCount + 1, $favoritesCount);

        // 商品詳細ページを再取得
        $response = $this->get("/item/{$item->id}");
        $response->assertSee((string)$favoritesCount);
    }

    /**
     * 追加済みのアイコンは色が変化する
     *
     * @return void
     */
    public function test_favorite_icon_color_changes()
    {
        // 全商品のコレクションを取得
        $items = Item::all();
        $users = User::all();
        // お気に入り登録にない商品とユーザーの組み合わせを取得
        foreach ($items as $item) {
            foreach ($users as $user) {
                if ($item->user_id !== $user->id) {
                    $noFavoritesByOthers  = Favorite::where('item_id', $item->id)
                        ->where('user_id', $user->id)
                        ->doesntExist();

                    // 出品者以外のいいねがついていない場合、この商品を返す
                    if ($noFavoritesByOthers) {
                        $tempItem = $item;
                        $tempUser = $user;
                        break;
                    }
                }
            }
            if ($noFavoritesByOthers) {
                break;
            }
        }
        $item = $tempItem;
        $user = $tempUser;
        $this->actingAs($user);

        // 商品詳細画面表示
        $response = $this->get("/item/{$item->id}");

        // 初期状態で「いいね追加済み」のアイコンが表示されていないことを確認
        $response->assertSee('images/favorite-inactive.png');
        $response->assertDontSee('/images/favorite-active.png');

        // いいねボタン押下
        $response = $this->get("/item/favorite/{$item->id}");
        $response->assertStatus(302);
        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        // 商品詳細ページを再取得
        $response = $this->get("/item/{$item->id}");

        // 「いいね後」のアイコンが正しく表示されていることを確認
        $response->assertDontSee('/images/favorite-inactive.png');
        $response->assertSee('/images/favorite-active.png');
    }

    /**
     *  再度いいねアイコンを押下することで、いいねを解除できる
     *
     * @return void
     */
    public function test_user_can_remove_favorite_from_item()
    {
        $favorite = Favorite::first();
        $item = Item::where('id', $favorite->item_id)->first();
        $user = User::where('id', $favorite->user_id)->first();
        $this->actingAs($user);

        // 商品詳細画面表示
        $this->get("/item/{$item->id}");
        $tempFavoritesCount = Favorite::where('item_id', $item->id)->count();

        // いいねボタン押下
        $response = $this->get("/item/favorite/{$item->id}");
        $response->assertStatus(302);
        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);
        $favoritesCount = Favorite::where('item_id', $item->id)->count();
        $this->assertEquals($tempFavoritesCount - 1, $favoritesCount);

        // 商品詳細ページを再取得
        $response = $this->get("/item/{$item->id}");
        $response->assertSee((string)$favoritesCount);
    }

    /**
     *  ログイン済みのユーザーはコメントを送信できる
     *
     * @return void
     */
    public function test_logged_in_user_can_post_comment()
    {
        $user = User::first();
        $this->actingAs($user);
        $item = Item::where('user_id', '!=', $user->id)->first();

        // 商品詳細画面表示
        $initialCommentCount = Comment::where('item_id', $item->id)->count();
        $this->get("/item/{$item->id}");

        // コメント送信
        $commentData = [
            'comment' => 'これはテストコメントです。',
            'item_id' => $item->id,
        ];
        $response = $this->post("/item/comment/", $commentData);
        $response->assertStatus(302);

        $this->assertDatabaseHas('comments', [
            'comment' => $commentData['comment'],
            'item_id' => $item->id,
            'user_id' => $user->id,
        ]);

        $finalCommentCount = Comment::where('item_id', $item->id)->count();
        $this->assertEquals($initialCommentCount + 1, $finalCommentCount);
    }

    /**
     *  ログイン前のユーザーはコメントを送信できない
     *
     * @return void
     */
    public function test_guest_user_cannot_post_comment()
    {
        // 商品詳細画面表示
        $item = Item::first(); // シーディングされた最初の商品
        $initialCommentCount = Comment::where('item_id', $item->id)->count();
        $this->get("/item/{$item->id}");

        // コメント送信
        $commentData = [
            'comment' => 'これはテストコメントです。',
            'item_id' => $item->id,
        ];
        $response = $this->post("/item/comment/", $commentData);
        $response->assertStatus(302);
        $response->assertRedirect('/login');

        $finalCommentCount = Comment::where('item_id', $item->id)->count();
        $this->assertEquals($initialCommentCount, $finalCommentCount);
    }

    /**
     *  コメントが入力されていない場合、バリデーションメッセージが表示される
     *
     * @return void
     */
    public function test_validation_message_is_displayed_when_comment_is_empty()
    {
        $user = User::first();
        $this->actingAs($user);
        $item = Item::where('user_id', '!=', $user->id)->first();

        // 商品詳細画面表示
        $this->get("/item/{$item->id}");

        // コメント送信
        $commentData = [
            'comment' => '',
            'item_id' => $item->id,
        ];
        $response = $this->post("/item/comment/", $commentData);
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['comment']);
    }

    /**
     *  コメントが255字以上の場合、バリデーションメッセージが表示される
     *
     * @return void
     */
    public function test_validation_message_is_displayed_when_comment_exceeds_255_characters()
    {
        $user = User::first();
        $this->actingAs($user);
        $item = Item::where('user_id', '!=', $user->id)->first();

        // 商品詳細画面表示
        $this->get("/item/{$item->id}");

        // コメント送信
        $longComment = str_repeat('a', 256);
        $commentData = [
            'comment' => $longComment,
            'item_id' => $item->id,
        ];
        $response = $this->post("/item/comment/", $commentData);
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['comment']);
    }
}
