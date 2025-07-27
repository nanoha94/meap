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
            ['name' => '大さじ', 'order' => 0],
            ['name' => '小さじ', 'order' => 1],
            ['name' => 'ml', 'order' => 2],
            ['name' => 'L', 'order' => 3],
            ['name' => 'g', 'order' => 4],
            ['name' => 'cc', 'order' => 5],
            ['name' => 'カップ', 'order' => 6],
            ['name' => '少々', 'order' => 7],
            ['name' => 'ひとつまみ', 'order' => 8],
            ['name' => 'cm', 'order' => 9],
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
            ['name' => '杯', 'order' => 24],
            ['name' => '切れ', 'order' => 25],
            ['name' => 'パック', 'order' => 26],
            ['name' => 'セット', 'order' => 27],
            ['name' => 'ケース', 'order' => 28],
        ];

        foreach ($units as $unit) {
            SeasoningUnit::create($unit);
        }
    }
}
