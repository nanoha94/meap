<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PublishedRecipe extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'recipe_id',
        'owner_user_id',
        'thumbnail_image_id',
        'name',
        'serving_count',
        'published_at',
        'last_published_at',
    ];

    /**
     * レシピを取得する
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * 作成者を取得する
     */
    public function ownerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * サムネイル画像を取得する
     */
    public function thumbnailImage(): BelongsTo
    {
        return $this->belongsTo(Image::class, 'thumbnail_image_id');
    }

    /**
     * 手順を取得する
     */
    public function steps(): HasMany
    {
        return $this->hasMany(PublishedRecipeStep::class, 'published_recipe_id')->orderBy('order');
    }

    /**
     * 食材を取得する
     */
    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(PublishedIngredient::class, 'published_recipe_ingredient_mappings', 'published_recipe_id', 'ingredient_id')
            ->withPivot('category_id', 'quantity', 'unit_name', 'unit_position', 'order')
            ->orderByPivot('order');
    }

    /**
     * カテゴリを取得する
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(PublishedRecipeCategory::class, 'published_recipe_category_mappings', 'published_recipe_id', 'category_id');
    }
}
