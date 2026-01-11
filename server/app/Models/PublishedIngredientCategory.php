<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PublishedIngredientCategory extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'order',
    ];

    /*
     * 公開食材を取得する
     */
    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(PublishedIngredient::class, 'published_recipe_ingredient_mappings', 'category_id', 'ingredient_id');
    }
}
