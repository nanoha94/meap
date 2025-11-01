<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

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

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function mealCategory()
    {
        return $this->belongsTo(MealCategory::class);
    }

    public function recipes()
    {
        return $this->belongsToMany(Recipe::class, 'meal_plan_recipe_mappings', 'meal_plan_id', 'recipe_id')
            ->withPivot('menu_category_id');
    }

    public function menuCategories()
    {
        return $this->belongsToMany(MenuCategory::class, 'meal_plan_recipe_mappings', 'meal_plan_id', 'menu_category_id')
            ->withPivot('recipe_id');
    }
}
