<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CourseType extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'group_id',
        'name',
        'order',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function mealPlans()
    {
        return $this->belongsToMany(MealPlan::class, 'meal_plan_recipe_mappings', 'course_type_id', 'meal_plan_id')
            ->withPivot('recipe_id');
    }

    public function recipes()
    {
        return $this->belongsToMany(Recipe::class, 'meal_plan_recipe_mappings', 'course_type_id', 'recipe_id')
            ->withPivot('meal_plan_id');
    }
}
