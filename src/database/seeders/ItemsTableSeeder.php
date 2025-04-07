<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ItemsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $param = [
            'name' => '腕時計',
            'price' => '15000',
            'description' => 'スタイリッシュなデザインのメンズ腕時計',
            'user_id' => '1',
            'condition_id' => '1',
            'image_path' => 'default/items/Armani+Mens+Clock.jpg',
        ];
        DB::table('items')->insert($param);

        $param = [
            'name' => 'HDD',
            'price' => '5000',
            'description' => '高速で信頼性の高いハードディスク',
            'user_id' => '1',
            'condition_id' => '2',
            'image_path' => 'default/items/HDD+Hard+Disk.jpg',
        ];
        DB::table('items')->insert($param);

        $param = [
            'name' => '玉ねぎ3束',
            'price' => '300',
            'description' => '新鮮な玉ねぎ3束のセット',
            'user_id' => '1',
            'condition_id' => '3',
            'image_path' => 'default/items/iLoveIMG+d.jpg',
        ];
        DB::table('items')->insert($param);

        $param = [
            'name' => '革靴',
            'price' => '4000',
            'description' => 'クラシックなデザインの革靴',
            'user_id' => '1',
            'condition_id' => '4',
            'image_path' => 'default/items/Leather+Shoes+Product+Photo.jpg',
        ];
        DB::table('items')->insert($param);

        $param = [
            'name' => 'ノートPC',
            'price' => '45000',
            'description' => '高性能なノートパソコン',
            'user_id' => '1',
            'condition_id' => '1',
            'image_path' => 'default/items/Living+Room+Laptop.jpg',
        ];
        DB::table('items')->insert($param);

        $param = [
            'name' => 'マイク',
            'price' => '8000',
            'description' => '高音質のレコーディング用マイク',
            'user_id' => '2',
            'condition_id' => '2',
            'image_path' => 'default/items/Music+Mic+4632231.jpg',
        ];
        DB::table('items')->insert($param);

        $param = [
            'name' => 'ショルダーバッグ',
            'price' => '3500',
            'description' => 'おしゃれなショルダーバッグ',
            'user_id' => '2',
            'condition_id' => '3',
            'image_path' => 'default/items/Purse+fashion+pocket.jpg',
        ];
        DB::table('items')->insert($param);

        $param = [
            'name' => 'タンブラー',
            'price' => '500',
            'description' => '使いやすいタンブラー',
            'user_id' => '2',
            'condition_id' => '4',
            'image_path' => 'default/items/Tumbler+souvenir.jpg',
        ];
        DB::table('items')->insert($param);

        $param = [
            'name' => 'コーヒーミル',
            'price' => '4000',
            'description' => '手動のコーヒーミル',
            'user_id' => '2',
            'condition_id' => '1',
            'image_path' => 'default/items/Waitress+with+Coffee+Grinder.jpg',
        ];
        DB::table('items')->insert($param);

        $param = [
            'name' => 'メイクセット',
            'price' => '2500',
            'description' => '便利なメイクアップセット',
            'user_id' => '2',
            'condition_id' => '2',
            'image_path' => 'default/items/外出メイクアップセット.jpg',
        ];
        DB::table('items')->insert($param);
    }
}
