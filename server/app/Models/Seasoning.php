<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Seasoning extends Model
{
    use  HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'group_id',
        'name'
    ];

    public function recipes()
    {
        return $this->belongsToMany(Recipe::class, 'recipe_seasoning_mappings', 'seasoning_id', 'recipe_id');
    }
}
