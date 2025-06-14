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
        return $this->belongsToMany(DishCategory::class, 'dish_category_mappings', 'dish_id', 'category_id');
    }

    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'dish_ingredient_mappings', 'dish_id', 'ingredient_id')
            ->withPivot('quantity', 'unit_id');
    }

    public function seasonings()
    {
        return $this->belongsToMany(Seasoning::class, 'dish_seasoning_mappings', 'dish_id', 'seasoning_id')
            ->withPivot('quantity', 'unit_id');
    }

    public function meals()
    {
        return $this->belongsToMany(Meal::class, 'meal_dish_mappings', 'dish_id', 'meal_id')
            ->withPivot('dish_role_id');
    }

    public function dishRoles()
    {
        return $this->belongsToMany(DishRole::class, 'meal_dish_mappings', 'dish_id', 'dish_role_id')
            ->withPivot('meal_id');
    }
}
