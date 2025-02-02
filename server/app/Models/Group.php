<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use  HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'group_size',
    ];

    public static function getGroupSize($id): int
    {
        if ($id === null) return 0;
        return GroupUser::where('group_id', $id)->count();
    }

    public function groupUsers(): HasMany
    {
        return $this->hasMany(GroupUser::class);
    }
}
