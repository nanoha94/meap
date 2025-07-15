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
        'meal_type_id',
        'date',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function mealType()
    {
        return $this->belongsTo(MealType::class);
    }

    public function recipes()
    {
        return $this->belongsToMany(Recipe::class, 'meal_plan_recipe_mappings', 'meal_plan_id', 'recipe_id')
            ->withPivot('course_type_id');
    }

    public function courseTypes()
    {
        return $this->belongsToMany(CourseType::class, 'meal_plan_recipe_mappings', 'meal_plan_id', 'course_type_id')
            ->withPivot('recipe_id');
    }
}
