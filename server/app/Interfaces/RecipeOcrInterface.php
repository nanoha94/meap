<?php

namespace App\Interfaces;

interface RecipeOcrInterface
{
    /**
     * 画像（base64 エンコード済み）からテキストを OCR 抽出する。
     *
     * 画像は API 呼び出しのみに使用し、サーバーには保存しない。
     *
     * @param  string  $base64Image  MIME プレフィックスなしの base64 文字列
     * @param  string  $mimeType  画像の MIME タイプ（例: image/jpeg）
     */
    public function extract(string $base64Image, string $mimeType): string;
}
