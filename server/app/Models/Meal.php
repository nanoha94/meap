<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Meal extends Model
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

    public function dishes()
    {
        return $this->belongsToMany(Dish::class, 'meal_dish_mappings', 'meal_id', 'dish_id');
    }

    public function dishRoles()
    {
        return $this->belongsToMany(DishRole::class, 'meal_dish_mappings', 'meal_id', 'dish_role_id');
    }
}
