<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class IngredientUnit extends Model
{
    use  HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'group_id',
        'name'
    ];

    public function dishes()
    {
        return $this->belongsToMany(Dish::class, 'dish_ingredient_mappings', 'unit_id', 'dish_id');
    }
}
