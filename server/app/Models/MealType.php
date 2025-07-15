<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MealType extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'group_id',
        'color_id',
        'name',
        'order',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function color()
    {
        return $this->belongsTo(Color::class);
    }
}
