<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\Ingredient;
use Illuminate\Database\Eloquent\Factories\Factory;

class IngredientFactory extends Factory
{
    protected $model = Ingredient::class;

    public function definition()
    {
        return [
            'group_id' => Group::factory(),
            'name' => $this->faker->name(),
        ];
    }
}
