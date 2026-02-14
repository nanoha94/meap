<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Meal extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'order',
        'meal_plan_id',
        'category_id',
    ];

    /**
     * 献立を取得する
     */
    public function mealPlan(): BelongsTo
    {
        return $this->belongsTo(MealPlan::class, 'meal_plan_id');
    }

    /**
     * 献立カテゴリを取得する
     */
    public function mealCategory(): BelongsTo
    {
        return $this->belongsTo(MealCategory::class, 'category_id');
    }

    /**
     * レシピを取得する
     */
    public function recipes(): BelongsToMany
    {
        return $this->belongsToMany(Recipe::class, 'meal_recipe_mappings', 'meal_id', 'recipe_id');
    }
}
