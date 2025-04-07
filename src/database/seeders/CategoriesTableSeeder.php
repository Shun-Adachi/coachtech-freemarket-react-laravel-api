<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $param = [
            'name' => 'ファッション',
        ];
        DB::table('categories')->insert($param);

        $param = [
            'name' => '家電',
        ];
        DB::table('categories')->insert($param);

        $param = [
            'name' => 'インテリア',
        ];
        DB::table('categories')->insert($param);

        $param = [
            'name' => 'レディース',
        ];
        DB::table('categories')->insert($param);

        $param = [
            'name' => 'メンズ',
        ];
        DB::table('categories')->insert($param);

        $param = [
            'name' => 'コスメ',
        ];
        DB::table('categories')->insert($param);

        $param = [
            'name' => '本',
        ];
        DB::table('categories')->insert($param);

        $param = [
            'name' => 'ゲーム',
        ];
        DB::table('categories')->insert($param);

        $param = [
            'name' => 'スポーツ',
        ];
        DB::table('categories')->insert($param);

        $param = [
            'name' => 'キッチン',
        ];
        DB::table('categories')->insert($param);

        $param = [
            'name' => 'ハンドメイド',
        ];
        DB::table('categories')->insert($param);

        $param = [
            'name' => 'アクセサリー',
        ];
        DB::table('categories')->insert($param);

        $param = [
            'name' => 'おもちゃ',
        ];
        DB::table('categories')->insert($param);

        $param = [
            'name' => 'ベビー・キッズ',
        ];
        DB::table('categories')->insert($param);
    }
}
