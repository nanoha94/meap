<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Dish extends Model
{
    use  HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'group_id',
        'name',
        'thumbnail_url',
        'url',
        'recipe',
        'memo',
    ];

    public function categories()
    {
        return $this->belongsToMany(DishCategory::class, 'dishes_category_mappings', 'dish_id', 'category_id');
    }

    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'dish_ingredient_mappings', 'dish_id', 'ingredient_id');
    }

    public function seasonings()
    {
        return $this->belongsToMany(Seasoning::class, 'dish_seasoning_mappings', 'dish_id', 'seasoning_id');
    }
}
