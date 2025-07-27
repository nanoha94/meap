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
            ['name' => 'g', 'order' => 0],
            ['name' => 'ml', 'order' => 1],
            ['name' => 'cc', 'order' => 2],
            ['name' => 'カップ', 'order' => 3],
            ['name' => '個', 'order' => 4],
            ['name' => '枚', 'order' => 5],
            ['name' => '本', 'order' => 6],
            ['name' => '片', 'order' => 7],
            ['name' => '粒', 'order' => 8],
            ['name' => '房', 'order' => 9],
            ['name' => '束', 'order' => 10],
            ['name' => '袋', 'order' => 11],
            ['name' => '缶', 'order' => 12],
            ['name' => '丁', 'order' => 13],
            ['name' => '合', 'order' => 14],
            ['name' => '杯', 'order' => 15],
            ['name' => '切れ', 'order' => 16],
            ['name' => 'パック', 'order' => 17],
            ['name' => 'セット', 'order' => 18],
            ['name' => 'ケース', 'order' => 19],
            ['name' => '大さじ', 'order' => 20],
            ['name' => '小さじ', 'order' => 21],
            ['name' => '少々', 'order' => 22],
            ['name' => 'ひとつまみ', 'order' => 23],
            ['name' => 'cm', 'order' => 24],
            ['name' => '滴', 'order' => 25],
            ['name' => '適量', 'order' => 26],
            ['name' => 'お好み', 'order' => 27],
            ['name' => 'L', 'order' => 28],
        ];

        foreach ($units as $unit) {
            IngredientUnit::create($unit);
        }
    }
}
