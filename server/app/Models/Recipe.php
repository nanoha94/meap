<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class Recipe extends Model
{
    use  HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'group_id',
        'name',
        'url',
        'memo',
    ];

    public function categories()
    {
        return $this->belongsToMany(RecipeCategory::class, 'recipe_category_mappings', 'recipe_id', 'category_id');
    }

    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'recipe_ingredient_mappings', 'recipe_id', 'ingredient_id')
            ->withPivot('quantity', 'unit_id', 'category_id', 'order');
    }

    public function ingredientCategories()
    {
        return $this->belongsToMany(IngredientCategory::class, 'recipe_ingredient_mappings', 'recipe_id', 'category_id');
    }

    public function ingredientUnits()
    {
        return $this->belongsToMany(IngredientUnit::class, 'recipe_ingredient_mappings', 'recipe_id', 'unit_id');
    }

    public function mealPlans()
    {
        return $this->belongsToMany(MealPlan::class, 'meal_plan_recipe_mappings', 'recipe_id', 'meal_plan_id')
            ->withPivot('menu_category_id');
    }

    public function menuCategories()
    {
        return $this->belongsToMany(MenuCategory::class, 'meal_plan_recipe_mappings', 'recipe_id', 'menu_category_id')
            ->withPivot('meal_plan_id');
    }

    public function steps(): BelongsToMany
    {
        return $this->belongsToMany(RecipeStep::class, 'recipe_step_mappings', 'recipe_id', 'step_id')
            ->withPivot('order');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function thumbnails(): BelongsToMany
    {
        return $this->belongsToMany(Image::class, 'image_mappings', 'related_id', 'image_id')
            ->wherePivot('related_model', static::class)
            ->wherePivot('image_type', 'thumbnail')
            ->orderBy('order');
    }
}
