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
        'group_id',
        'name',
        'order'
    ];


    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }


    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'recipe_ingredient_mappings', 'category_id', 'ingredient_id');
    }
}
