<?php

namespace Database\Seeders;

use App\Models\SeasoningUnit;
use Illuminate\Database\Seeder;

class SeasoningUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            ['name' => '大さじ', 'order' => 1],
            ['name' => '小さじ', 'order' => 2],
            ['name' => 'ml', 'order' => 3],
            ['name' => 'L', 'order' => 4],
            ['name' => 'g', 'order' => 5],
            ['name' => 'cc', 'order' => 6],
            ['name' => 'カップ', 'order' => 7],
            ['name' => '少々', 'order' => 8],
            ['name' => 'ひとつまみ', 'order' => 9],
            ['name' => '滴', 'order' => 10],
            ['name' => '適量', 'order' => 11],
            ['name' => 'お好み', 'order' => 12],
            ['name' => '個', 'order' => 13],
            ['name' => '枚', 'order' => 14],
            ['name' => '本', 'order' => 15],
            ['name' => '片', 'order' => 16],
            ['name' => '粒', 'order' => 17],
            ['name' => '房', 'order' => 18],
            ['name' => '束', 'order' => 19],
            ['name' => '丁', 'order' => 20],
            ['name' => '袋', 'order' => 21],
            ['name' => '缶', 'order' => 22],
            ['name' => '合', 'order' => 23],
            ['name' => 'パック', 'order' => 24],
            ['name' => 'セット', 'order' => 25],
            ['name' => 'ケース', 'order' => 26],
        ];

        foreach ($units as $unit) {
            SeasoningUnit::create($unit);
        }
    }
}
