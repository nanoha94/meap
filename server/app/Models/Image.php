<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'src',
        'width',
        'height',
    ];

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'image_mappings', 'image_id', 'group_id')
            ->withPivot('related_model', 'related_id', 'image_type', 'order');
    }
}
