<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        'order',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_checked' => 'boolean',
    ];

    /**
     * グループを取得する
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * 買い物カテゴリを取得する
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ShoppingCategory::class, 'category_id');
    }

    /**
     * 買い物タグを取得する
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ShoppingTag::class, 'shopping_item_tag_mappings', 'item_id', 'tag_id');
    }
}
