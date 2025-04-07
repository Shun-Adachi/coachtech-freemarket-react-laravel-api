<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $param = [
            'name' => '管理者',
            'email' => 'root@example.com',
            'password' => Hash::make('rootroot'),
            'current_post_code' => '000-0000',
            'current_address' => '東京都',
            'current_building' => '',
            'shipping_post_code' => '000-0000',
            'shipping_address' => '東京都',
            'shipping_building' => '',
            'payment_method_id' => '1',
            'thumbnail_path' => 'default/users/admin.jpg'
        ];
        DB::table('users')->insert($param);

        $param = [
            'name' => 'テスト五郎',
            'email' => 'test@example.com',
            'password' => Hash::make('testtest'),
            'current_post_code' => '001-0001',
            'current_address' => '愛知県',
            'current_building' => 'テストビル',
            'shipping_post_code' => '001-0001',
            'shipping_address' => '愛知県',
            'shipping_building' => 'テストビル',
            'payment_method_id' => '1',
            'thumbnail_path' => 'default/users/testgoro.jpg'
        ];
        DB::table('users')->insert($param);

        $param = [
            'name' => 'hoge',
            'email' => 'hoge@example.com',
            'password' => Hash::make('hogehoge'),
            'current_post_code' => '111-1111',
            'current_address' => '大阪府',
            'current_building' => 'サンプル101',
            'shipping_post_code' => '111-1111',
            'shipping_address' => '大阪府',
            'shipping_building' => 'サンプル101',
            'payment_method_id' => '1',
            'thumbnail_path' => NULL
        ];
        DB::table('users')->insert($param);
    }
}
