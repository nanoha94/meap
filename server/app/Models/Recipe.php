<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    use  HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'group_id',
        'name',
        'thumbnail_url',
        'thumbnail_width',
        'thumbnail_height',
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

    public function mealPlans()
    {
        return $this->belongsToMany(MealPlan::class, 'meal_plan_recipe_mappings', 'recipe_id', 'meal_plan_id')
            ->withPivot('course_type_id');
    }

    public function courseTypes()
    {
        return $this->belongsToMany(CourseType::class, 'meal_plan_recipe_mappings', 'recipe_id', 'course_type_id')
            ->withPivot('meal_plan_id');
    }

    public function steps()
    {
        return $this->hasMany(RecipeStep::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
