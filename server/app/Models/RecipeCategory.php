<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RecipeCategory extends Model
{
    use  HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'group_id',
        'name',
        'color',
        'order',
    ];

    public function recipes()
    {
        return $this->belongsToMany(Recipe::class, 'recipe_category_mappings', 'category_id', 'recipe_id');
    }
}
