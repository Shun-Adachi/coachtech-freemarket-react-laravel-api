<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Favorite;
use App\Models\Item;
use App\Models\User;

class FavoriteFactory extends Factory
{
    protected $model = Favorite::class;

    public function definition()
    {
        static $combinations = [];
        static $ensuredUserIds = [];

        // 確実にUser_idが1, 2, 3を含むようにする
        if (count($ensuredUserIds) < 3) {
            $userId = [1, 2, 3][count($ensuredUserIds)];

            $item = Item::where('user_id', '!=', $userId)->inRandomOrder()->first();

            if ($item) {
                $combinations[] = [$userId, $item->id];
                $ensuredUserIds[] = $userId;

                return [
                    'user_id' => $userId,
                    'item_id' => $item->id,
                ];
            }
        }

        // ランダムにお気に入りを生成（重複を防ぐ）
        do {
            $item = Item::inRandomOrder()->first();
            $user = User::where('id', '!=', $item->user_id)->inRandomOrder()->first();
        } while (in_array([$user->id, $item->id], $combinations));

        $combinations[] = [$user->id, $item->id];

        return [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ];
    }
}
