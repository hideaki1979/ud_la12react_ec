<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
            ['name' => '黒ボールペン', 'code' => 'P001', 'price' => 201, 'img' => '1.jpg', 'active' => 0],
            ['name' => '赤ボールペン', 'code' => 'P002', 'price' => 202, 'img' => '2.jpg', 'active' => 0],
            ['name' => '青ボールペン', 'code' => 'P003', 'price' => 203, 'img' => '3.jpg', 'active' => 0],
            ['name' => '橙ボールペン', 'code' => 'P004', 'price' => 204, 'img' => '4.jpg', 'active' => 0],
            ['name' => '緑ボールペン', 'code' => 'P005', 'price' => 205, 'img' => '5.jpg', 'active' => 0],
            ['name' => '黒鉛筆', 'code' => 'P006', 'price' => 206, 'img' => '6.jpg', 'active' => 0],
            ['name' => '赤鉛筆', 'code' => 'P007', 'price' => 207, 'img' => '7.jpg', 'active' => 0],
            ['name' => '青鉛筆', 'code' => 'P008', 'price' => 208, 'img' => '8.jpg', 'active' => 0],
            ['name' => '緑鉛筆', 'code' => 'P009', 'price' => 209, 'img' => '9.jpg', 'active' => 0],
            ['name' => '紫鉛筆', 'code' => 'P010', 'price' => 210, 'img' => '10.jpg', 'active' => 0],
            ['name' => '黄鉛筆', 'code' => 'P011', 'price' => 211, 'img' => '11.jpg', 'active' => 1],
            ['name' => '茶鉛筆', 'code' => 'P012', 'price' => 212, 'img' => '12.jpg', 'active' => 0],
            ['name' => '黄緑鉛筆', 'code' => 'P013', 'price' => 213, 'img' => '13.jpg', 'active' => 1],
            ['name' => 'ピンク鉛筆', 'code' => 'P014', 'price' => 214, 'img' => '14.jpg', 'active' => 0],
        ];

        foreach ($products as $product) {
            DB::table('products')->insert([
                'name' => $product['name'],
                'code' => $product['code'],
                'price' => $product['price'],
                'img' => $product['img'],
                'active' => $product['active'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        }
    }
}
