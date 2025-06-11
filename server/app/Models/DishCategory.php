<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DishCategory extends Model
{
    use  HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'group_id',
        'name',
    ];

    public function dishes()
    {
        return $this->belongsToMany(Dish::class, 'dish_category_mappings', 'category_id', 'dish_id');
    }
}
