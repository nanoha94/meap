<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use  HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'group_size',
    ];

    // Groupを作成
    public static function createGroup()
    {
        $group = self::create([
            'group_size' => 1,
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

    // グループに属するのユーザー数を取得
    public function getGroupSize(): int
    {
        return $this->users()->count();
    }

    public function users()
    {
        return $this->hasManyThrough(User::class, GroupUserMapping::class, 'group_id', 'id', 'id', 'user_id');
    }

    public function shoppingItems()
    {
        return $this->hasMany(ShoppingItem::class);
    }

    public function shoppingCategories()
    {
        return $this->hasMany(ShoppingCategory::class);
    }

    public function shoppingTags()
    {
        return $this->hasMany(ShoppingTag::class);
    }
}
