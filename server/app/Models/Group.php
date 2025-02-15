<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use  HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'group_size',
    ];

    // Groupモデルにメソッドを追加
    public static function createGroup($inviter)
    {
        $group = self::create([
            'group_size' => 0,
        ]);

        // カテゴリを追加
        $category = new ShoppingCategory();
        $category->name = "その他のカテゴリー";
        $category->group_id = $group->id;
        $category->is_default = true;
        $category->order = 0;
        $category->save();

        return $group;
    }

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
