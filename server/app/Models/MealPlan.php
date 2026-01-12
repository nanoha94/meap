<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MealPlan extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'group_id',
        'meal_category_id',
        'date',
    ];

    /**
     * グループを取得する
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * 献立カテゴリを取得する
     */
    public function mealCategory(): BelongsTo
    {
        return $this->belongsTo(MealCategory::class);
    }

    /**
     * レシピを取得する
     */
    public function recipes(): BelongsToMany
    {
        return $this->belongsToMany(Recipe::class, 'meal_plan_recipe_mappings', 'meal_plan_id', 'recipe_id')
            ->withPivot('menu_category_id');
    }

    /**
     * 献立カテゴリを取得する
     */
    public function menuCategories(): BelongsToMany
    {
        return $this->belongsToMany(MenuCategory::class, 'meal_plan_recipe_mappings', 'meal_plan_id', 'menu_category_id')
            ->withPivot('recipe_id');
    }
}
