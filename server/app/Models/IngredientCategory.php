<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class IngredientCategory extends Model
{
    use  HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'recipe_id',
        'name',
        'is_default',
        'order',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * レシピを取得する
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * 食材を取得する
     */
    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'recipe_ingredient_mappings', 'category_id', 'ingredient_id')
            ->withPivot('quantity', 'quantity_display', 'unit_id', 'order')
            ->orderByPivot('order');
    }
}
