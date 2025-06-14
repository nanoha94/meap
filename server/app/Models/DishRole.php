<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DishRole extends Model
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

    public function meals()
    {
        return $this->belongsToMany(Meal::class, 'meal_dish_mappings', 'dish_role_id', 'meal_id')
            ->withPivot('dish_id');
    }

    public function dishes()
    {
        return $this->belongsToMany(Dish::class, 'meal_dish_mappings', 'dish_role_id', 'dish_id')
            ->withPivot('meal_id');
    }
}
