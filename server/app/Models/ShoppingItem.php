<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShoppingItem extends Model
{
    use  HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'group_id',
        'category_id',
        'name',
        'is_pinned',
        'is_checked',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ShoppingCategory::class);
    }
}
