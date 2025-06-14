<?php

namespace Database\Seeders;

use App\Models\Color;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ColorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $colors = [
            ['name' => 'イエロー', 'color_code_hex' => '#F5B12E', 'order' => 0],
            ['name' => 'オレンジ', 'color_code_hex' => '#F0762D', 'order' => 1],
            ['name' => 'ピンク', 'color_code_hex' => '#F89DC0', 'order' => 2],
            ['name' => 'レッド', 'color_code_hex' => '#EC3D33', 'order' => 3],
            ['name' => 'イエローグリーン', 'color_code_hex' => '#88C63C', 'order' => 4],
            ['name' => 'グリーン', 'color_code_hex' => '#4FA260', 'order' => 5],
            ['name' => 'スカイブルー', 'color_code_hex' => '#439CFE', 'order' => 6],
            ['name' => 'ブルー', 'color_code_hex' => '#2673B8', 'order' => 7],
            ['name' => 'パープル', 'color_code_hex' => '#6746B9', 'order' => 8],
        ];

        foreach ($colors as $color) {
            Color::create($color);
        }
    }
}
