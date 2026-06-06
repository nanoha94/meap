<?php

namespace Database\Factories;

use App\Enums\GroupPlan;
use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition()
    {
        return [
            'group_size' => $this->faker->numberBetween(1, 10),
            'plan' => GroupPlan::FREE,
            'ai_usage_count' => 0,
            'ai_usage_reset_at' => now()->addMonth(),
        ];
    }
}
