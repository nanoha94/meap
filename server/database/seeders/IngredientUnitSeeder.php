<?php

namespace Database\Seeders;

use App\Models\IngredientUnit;
use Illuminate\Database\Seeder;

class IngredientUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            ['name' => 'g', 'order' => 1],
            ['name' => 'ml', 'order' => 2],
            ['name' => 'cc', 'order' => 3],
            ['name' => 'カップ', 'order' => 4],
            ['name' => '個', 'order' => 5],
            ['name' => '枚', 'order' => 6],
            ['name' => '本', 'order' => 7],
            ['name' => '片', 'order' => 8],
            ['name' => '粒', 'order' => 9],
            ['name' => '房', 'order' => 10],
            ['name' => '束', 'order' => 11],
            ['name' => '袋', 'order' => 12],
            ['name' => '缶', 'order' => 13],
            ['name' => '丁', 'order' => 14],
            ['name' => '合', 'order' => 15],
            ['name' => 'パック', 'order' => 16],
            ['name' => 'セット', 'order' => 17],
            ['name' => 'ケース', 'order' => 18],
            ['name' => '大さじ', 'order' => 19],
            ['name' => '小さじ', 'order' => 20],
            ['name' => '少々', 'order' => 21],
            ['name' => 'ひとつまみ', 'order' => 22],
            ['name' => '滴', 'order' => 23],
            ['name' => '適量', 'order' => 24],
            ['name' => 'お好み', 'order' => 25],
            ['name' => 'L', 'order' => 26],
        ];

        foreach ($units as $unit) {
            IngredientUnit::create($unit);
        }
    }
}
