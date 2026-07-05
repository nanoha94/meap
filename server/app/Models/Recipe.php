<?php

namespace App\Models;

use App\Enums\RecipeSource;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    use  HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'group_id',
        'owner_user_id',
        'published_recipe_id',
        'name',
        'url',
        'memo',
        'serving_count',
        'cooking_time',
        'status',
        'source',
    ];

    protected $casts = [
        'source' => RecipeSource::class,
    ];

    /**
     * 料理カテゴリを取得する
     */
    public function categories()
    {
        return $this->belongsToMany(RecipeCategory::class, 'recipe_category_mappings', 'recipe_id', 'category_id');
    }

    /**
     * 食材を取得する
     */
    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'recipe_ingredient_mappings', 'recipe_id', 'ingredient_id')
            ->withPivot('quantity', 'unit_id', 'category_id', 'order')
            ->using(RecipeIngredientPivot::class);
    }

    /**
     * 食材カテゴリを取得する
     */
    public function ingredientCategories()
    {
        return $this->belongsToMany(IngredientCategory::class, 'recipe_ingredient_mappings', 'recipe_id', 'category_id');
    }

    /**
     * 食材単位を取得する
     */
    public function ingredientUnits()
    {
        return $this->belongsToMany(IngredientUnit::class, 'recipe_ingredient_mappings', 'recipe_id', 'unit_id');
    }

    /**
     * 献立を取得する
     */
    public function mealPlans()
    {
        return $this->belongsToMany(MealPlan::class, 'meal_plan_recipe_mappings', 'recipe_id', 'meal_plan_id');
    }

    /**
     * 料理手順を取得する
     */
    public function steps(): HasMany
    {
        return $this->hasMany(RecipeStep::class, 'recipe_id')
            ->orderBy('order');
    }

    /**
     * グループを取得する
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * 作成者を取得する
     */
    public function ownerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * 公開レシピを取得する
     */
    public function publishedRecipe(): BelongsTo
    {
        return $this->belongsTo(PublishedRecipe::class, 'published_recipe_id');
    }

    /**
     * サムネイルを取得する
     */
    public function thumbnails(): BelongsToMany
    {
        return $this->belongsToMany(Image::class, 'image_mappings', 'related_id', 'image_id')
            ->wherePivot('related_model', static::class)
            ->wherePivot('image_type', 'thumbnail')
            ->orderByPivot('order');
    }
}
