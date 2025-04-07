<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Comment;
use App\Models\Item;
use App\Models\User;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition()
    {
        // 商品をランダムに取得
        $item = item::inRandomOrder()->first();

        // 出品者以外のユーザーをランダムに取得
        $user = User::where('id', '!=', $item->user_id)->inRandomOrder()->first();

        return [
            'comment' => $this->faker->text(255),
            'user_id' => $user->id,
            'item_id' => $item->id,
        ];
    }
}
