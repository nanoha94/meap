<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImageMapping extends Model
{
    protected $table = 'image_mappings';
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'image_id',
        'related_id',
        'group_id',
        'related_model',
        'image_type',
        'order',
    ];
}
