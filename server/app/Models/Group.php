<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        try {
            return DB::transaction(function () {
                $group = self::create([
                    'group_size' => 1,
                ]);

                // デフォルトの買い物カテゴリを追加
                $category = new ShoppingCategory();
                $category->name = "その他のカテゴリー";
                $category->group_id = $group->id;
                $category->is_default = true;
                $category->order = 0;
                $category->save();

                // デフォルトの料理分類を追加
                $courseTypes = [
                    ['name' => '主食', 'order' => 0],
                    ['name' => '主菜', 'order' => 1],
                    ['name' => '副菜', 'order' => 2],
                    ['name' => '汁物', 'order' => 3],
                    ['name' => 'その他', 'order' => 4],
                ];

                foreach ($courseTypes as $courseType) {
                    $courseTypeObj = new CourseType();
                    $courseTypeObj->group_id = $group->id;
                    $courseTypeObj->name = $courseType['name'];
                    $courseTypeObj->order = $courseType['order'];
                    $courseTypeObj->save();
                }

                // デフォルトの献立種別を追加
                $yellow = Color::where('name', 'イエロー')->first();
                $red = Color::where('name', 'レッド')->first();
                $blue = Color::where('name', 'ブルー')->first();
                $categories = [
                    ['name' => '朝食', 'color_id' => $yellow->id, 'order' => 0],
                    ['name' => '昼食', 'color_id' => $red->id, 'order' => 1],
                    ['name' => '夕食', 'color_id' => $blue->id, 'order' => 2],
                ];

                foreach ($categories as $category) {
                    $mealType = new MealType();
                    $mealType->group_id = $group->id;
                    $mealType->color_id = $category['color_id'];
                    $mealType->name = $category['name'];
                    $mealType->order = $category['order'];
                    $mealType->save();
                }

                return $group;
            });
        } catch (\Throwable $e) {
            Log::error('グループ作成エラー', [
                'function' => 'Group@createGroup',
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
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

    public function mealTypes()
    {
        return $this->hasMany(MealType::class);
    }

    public function mealPlans()
    {
        return $this->hasMany(MealPlan::class);
    }

    public function recipes()
    {
        return $this->hasMany(Recipe::class);
    }

    public function recipeCategories()
    {
        return $this->hasMany(RecipeCategory::class);
    }

    public function courseTypes()
    {
        return $this->hasMany(CourseType::class);
    }

    public function seasonings()
    {
        return $this->hasMany(Seasoning::class);
    }

    public function seasoningUnits()
    {
        return $this->hasMany(SeasoningUnit::class);
    }

    public function ingredients()
    {
        return $this->hasMany(Ingredient::class);
    }

    public function ingredientUnits()
    {
        return $this->hasMany(IngredientUnit::class);
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
