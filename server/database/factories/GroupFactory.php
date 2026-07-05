<?php

namespace Database\Factories;

use App\Enums\GroupPlan;
use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Group モデル用 Factory（テスト・関連 Factory 専用）。
 *
 * 本番コードでは使わない。Group::factory() 経由で呼ばれる。
 * - AutoComplementTest など、デフォルトカテゴリ不要なテスト
 * - IngredientFactory の group_id 自動生成
 *
 * デフォルトカテゴリ・単位などが必要な場合は Group::createGroup() を使う。
 */
class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition()
    {
        return [
            'group_size' => $this->faker->numberBetween(1, 10),
            'plan' => GroupPlan::FREE,
            'ai_monthly_remaining' => GroupPlan::FREE->monthlyLimit(),
            'ai_usage_reset_at' => now()->addMonth(),
        ];
    }
}
