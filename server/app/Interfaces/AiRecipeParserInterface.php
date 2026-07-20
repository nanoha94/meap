<?php

namespace App\Interfaces;

use App\Data\ParsedRecipe;

interface AiRecipeParserInterface
{
    /**
     * 画像（base64 エンコード済み）からレシピ情報を解析する。
     *
     * 画像は解析のみに使用し、サーバーには保存しない。
     *
     * @param  list<string>  $unitNames  グループの単位マスタ名一覧
     */
    public function parseImage(string $base64Image, array $unitNames): ParsedRecipe;

    /**
     * URL 先の Web ページからレシピ情報を解析する。
     *
     * HTML は解析のみに使用し、サーバーには保存しない。
     *
     * @param  list<string>  $unitNames  グループの単位マスタ名一覧
     */
    public function parseUrl(string $url, array $unitNames): ParsedRecipe;
}
