<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MenuCategory extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'group_id',
        'name',
        'order',
    ];

    /**
     * グループを取得する
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * 献立を取得する
     */
    public function mealPlans(): BelongsToMany
    {
        return $this->belongsToMany(MealPlan::class, 'meal_plan_recipe_mappings', 'menu_category_id', 'meal_plan_id')
            ->withPivot('recipe_id');
    }

    /**
     * レシピを取得する
     */
    public function recipes(): BelongsToMany
    {
        return $this->belongsToMany(Recipe::class, 'meal_plan_recipe_mappings', 'menu_category_id', 'recipe_id')
            ->withPivot('meal_plan_id');
    }
}
