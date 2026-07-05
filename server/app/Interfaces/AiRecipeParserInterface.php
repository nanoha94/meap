<?php

namespace App\Interfaces;

use App\Data\ParsedRecipe;

interface AiRecipeParserInterface
{
    /**
     * 画像（base64 エンコード済み）からレシピ情報を解析する。
     *
     * 画像は解析のみに使用し、サーバーには保存しない。
     */
    public function parseImage(string $base64Image): ParsedRecipe;
}
