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
        'order',
    ];

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function images()
    {
        return $this->belongsToMany(Image::class, 'image_mappings', 'related_id', 'image_id')
            ->wherePivot('related_model', static::class)
            ->wherePivot('image_type', 'image');
    }
}
