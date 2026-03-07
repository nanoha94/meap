<?php

namespace App\Models;

use App\Services\ImageService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Group extends Model
{
    use HasUuids;
    use HasFactory;

    protected $keyType = 'string';

    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function (Group $group): void {
            app(ImageService::class)->deleteImagesByGroup($group);
        });
    }
    public $incrementing = false;

    protected $fillable = [
        'group_size',
    ];

    // Groupを作成
    public static function createGroup(): Group
    {
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

            // デフォルトの食材カテゴリを追加
            $ingredientCategory = new IngredientCategory();
            $ingredientCategory->name = "食材";
            $ingredientCategory->group_id = $group->id;
            $ingredientCategory->is_default = true;
            $ingredientCategory->order = 0;
            $ingredientCategory->save();

            // デフォルトの献立カテゴリを追加
            $yellow = Color::where('name', 'イエロー')->first();
            $red = Color::where('name', 'レッド')->first();
            $blue = Color::where('name', 'ブルー')->first();
            $categories = [
                ['name' => '朝食', 'color_id' => $red->id, 'order' => 0],
                ['name' => '昼食', 'color_id' => $yellow->id, 'order' => 1],
                ['name' => '夕食', 'color_id' => $blue->id, 'order' => 2],
            ];

            foreach ($categories as $category) {
                $mealCategory = new MealCategory();
                $mealCategory->group_id = $group->id;
                $mealCategory->color_id = $category['color_id'];
                $mealCategory->name = $category['name'];
                $mealCategory->order = $category['order'];
                $mealCategory->save();
            }

            // デフォルトの食材単位を追加
            $units = [
                // 数値 + 単位（suffix）
                ['name' => 'g', 'position' => 'suffix', 'requires_quantity' => true, 'order' => 0],
                ['name' => 'ml', 'position' => 'suffix', 'requires_quantity' => true, 'order' => 1],
                ['name' => 'cc', 'position' => 'suffix', 'requires_quantity' => true, 'order' => 2],
                ['name' => 'カップ', 'position' => 'suffix', 'requires_quantity' => true, 'order' => 3],
                ['name' => '個', 'position' => 'suffix', 'requires_quantity' => true, 'order' => 4],
                ['name' => '枚', 'position' => 'suffix', 'requires_quantity' => true, 'order' => 5],
                ['name' => '本', 'position' => 'suffix', 'requires_quantity' => true, 'order' => 6],
                ['name' => '片', 'position' => 'suffix', 'requires_quantity' => true, 'order' => 7],
                ['name' => '粒', 'position' => 'suffix', 'requires_quantity' => true, 'order' => 8],
                ['name' => '房', 'position' => 'suffix', 'requires_quantity' => true, 'order' => 9],
                ['name' => '束', 'position' => 'suffix', 'requires_quantity' => true, 'order' => 10],
                ['name' => '袋', 'position' => 'suffix', 'requires_quantity' => true, 'order' => 11],
                ['name' => '缶', 'position' => 'suffix', 'requires_quantity' => true, 'order' => 12],
                ['name' => '丁', 'position' => 'suffix', 'requires_quantity' => true, 'order' => 13],
                ['name' => '合', 'position' => 'suffix', 'requires_quantity' => true, 'order' => 14],
                ['name' => '杯', 'position' => 'suffix', 'requires_quantity' => true, 'order' => 15],
                ['name' => '切れ', 'position' => 'suffix', 'requires_quantity' => true, 'order' => 16],
                ['name' => 'パック', 'position' => 'suffix', 'requires_quantity' => true, 'order' => 17],
                ['name' => 'セット', 'position' => 'suffix', 'requires_quantity' => true, 'order' => 18],
                ['name' => 'ケース', 'position' => 'suffix', 'requires_quantity' => true, 'order' => 19],
                ['name' => 'cm', 'position' => 'suffix', 'requires_quantity' => true, 'order' => 27],
                ['name' => '滴', 'position' => 'suffix', 'requires_quantity' => true, 'order' => 28],
                ['name' => 'L', 'position' => 'suffix', 'requires_quantity' => true, 'order' => 29],

                // 単位 + 数値（prefix）
                ['name' => '大さじ', 'position' => 'prefix', 'requires_quantity' => true, 'order' => 20],
                ['name' => '小さじ', 'position' => 'prefix', 'requires_quantity' => true, 'order' => 21],

                // 数値不要な単位（prefix）
                ['name' => '少々', 'position' => 'prefix', 'requires_quantity' => false, 'order' => 22],
                ['name' => 'ひとつまみ', 'position' => 'prefix', 'requires_quantity' => false, 'order' => 23],
                ['name' => 'ふたつまみ', 'position' => 'prefix', 'requires_quantity' => false, 'order' => 24],
                ['name' => '適量', 'position' => 'prefix', 'requires_quantity' => false, 'order' => 25],
                ['name' => 'お好み', 'position' => 'prefix', 'requires_quantity' => false, 'order' => 26],
            ];

            foreach ($units as $unit) {
                $unit = new IngredientUnit($unit);
                $unit->group_id = $group->id;
                $unit->save();
            }

            return $group;
        });
    }

    /**
     * グループに属するユーザー数を更新
     */
    public function refreshGroupSize(): void
    {
        $this->group_size = $this->users()->count();
        $this->save();

        if ($this->group_size === 0) {
            $this->delete();
        }
    }

    /**
     * グループに属するのユーザー数を取得
     */
    public function getGroupSize(): int
    {
        return $this->users()->count();
    }

    /*
     * グループに属するユーザーを取得
     */
    public function users(): BelongsToMany
    {
        // 1つのグループに複数のユーザーが属する（各ユーザーは1つのグループにのみ属する）
        // 中間テーブルを使用するため、belongsToManyを使用
        return $this->belongsToMany(User::class, 'group_user_mappings', 'group_id', 'user_id');
    }

    /**
     * 献立カテゴリを取得する
     */
    public function mealCategories(): HasMany
    {
        return $this->hasMany(MealCategory::class);
    }

    /**
     * 献立を取得する
     */
    public function mealPlans(): HasMany
    {
        return $this->hasMany(MealPlan::class);
    }

    /**
     * レシピを取得する
     */
    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }

    /**
     * 料理分類を取得する
     */
    public function recipeCategories(): HasMany
    {
        return $this->hasMany(RecipeCategory::class);
    }

    /**
     * 食材を取得する
     */
    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class);
    }

    /**
     * 食材カテゴリを取得する
     */
    public function ingredientCategories(): HasMany
    {
        return $this->hasMany(IngredientCategory::class);
    }

    /**
     * 食材単位を取得する
     */
    public function ingredientUnits(): HasMany
    {
        return $this->hasMany(IngredientUnit::class);
    }

    /**
     * 買い物リストを取得する
     */
    public function shoppingItems(): HasMany
    {
        return $this->hasMany(ShoppingItem::class);
    }

    /**
     * 買い物カテゴリを取得する
     */
    public function shoppingCategories(): HasMany
    {
        return $this->hasMany(ShoppingCategory::class);
    }

    /**
     * 買い物タグを取得する
     */
    public function shoppingTags(): HasMany
    {
        return $this->hasMany(ShoppingTag::class);
    }

    /**
     * 画像を取得する
     */
    public function images(): BelongsToMany
    {
        return $this->belongsToMany(Image::class, 'image_mappings', 'group_id', 'image_id')
            ->withPivot('related_model', 'related_id', 'image_type', 'order');
    }
}
