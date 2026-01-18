<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'ペン', 'slug' => 'pen', 'sort_order' => 1],
            ['name' => '鉛筆', 'slug' => 'pencil', 'sort_order' => 2],
            ['name' => '消しゴム', 'slug' => 'eraser', 'sort_order' => 3],
            ['name' => 'ノート', 'slug' => 'notebook', 'sort_order' => 4],
            ['name' => 'その他', 'slug' => 'other', 'sort_order' => 99],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
