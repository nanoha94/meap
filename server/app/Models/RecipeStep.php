<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RecipeStep extends Model
{
    use  HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'recipe_id',
        'instruction',
        'image_url',
        'image_width',
        'image_height',
        'order',
    ];

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }
}
