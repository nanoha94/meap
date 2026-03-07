<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\Pivot;

class RecipeIngredientPivot extends Pivot
{
    /**
     * quantity の取得時キャスト
     * DB の FLOAT が PHP で文字列として返るため float に統一。null はそのまま返す。
     */
    protected function quantity(): Attribute
    {
        return Attribute::get(fn (mixed $value) => $value === null ? null : (float) $value);
    }
}
