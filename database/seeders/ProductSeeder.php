<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            ['name' => '黒ボールペン', 'code' => 'P001', 'price' => 201, 'img' => '1.jpg', 'active' => false],
            ['name' => '赤ボールペン', 'code' => 'P002', 'price' => 202, 'img' => '2.jpg', 'active' => false],
            ['name' => '青ボールペン', 'code' => 'P003', 'price' => 203, 'img' => '3.jpg', 'active' => false],
            ['name' => '橙ボールペン', 'code' => 'P004', 'price' => 204, 'img' => '4.jpg', 'active' => false],
            ['name' => '緑ボールペン', 'code' => 'P005', 'price' => 205, 'img' => '5.jpg', 'active' => false],
            ['name' => '黒鉛筆', 'code' => 'P006', 'price' => 206, 'img' => '6.jpg', 'active' => false],
            ['name' => '赤鉛筆', 'code' => 'P007', 'price' => 207, 'img' => '7.jpg', 'active' => false],
            ['name' => '青鉛筆', 'code' => 'P008', 'price' => 208, 'img' => '8.jpg', 'active' => false],
            ['name' => '緑鉛筆', 'code' => 'P009', 'price' => 209, 'img' => '9.jpg', 'active' => false],
            ['name' => '紫鉛筆', 'code' => 'P010', 'price' => 210, 'img' => '10.jpg', 'active' => false],
            ['name' => '黄鉛筆', 'code' => 'P011', 'price' => 211, 'img' => '11.jpg', 'active' => true],
            ['name' => '茶鉛筆', 'code' => 'P012', 'price' => 212, 'img' => '12.jpg', 'active' => false],
            ['name' => '黄緑鉛筆', 'code' => 'P013', 'price' => 213, 'img' => '13.jpg', 'active' => true],
            ['name' => 'ピンク鉛筆', 'code' => 'P014', 'price' => 214, 'img' => '14.jpg', 'active' => false],
        ];

        $now = Carbon::now();
        $data = array_map(function ($product) use ($now) {
            return $product + ['created_at' => $now, 'updated_at' => $now];
        }, $products);
        DB::table('products')->insert($data);
    }
}
