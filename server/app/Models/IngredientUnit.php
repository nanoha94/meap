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
        'name',
        'position',
        'requires_quantity',
        'order'
    ];

    protected $casts = [
        'requires_quantity' => 'boolean',
    ];
}
