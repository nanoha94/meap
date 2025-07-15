<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('meal_plan_recipe_mappings', function (Blueprint $table) {
            $table->foreignUuid('meal_plan_id')->constrained('meal_plans', 'id')->cascadeOnDelete();
            $table->foreignUuid('recipe_id')->constrained('recipes', 'id')->cascadeOnDelete();
            $table->foreignUuid('course_type_id')->constrained('course_types', 'id')->cascadeOnDelete();
            $table->primary(['meal_plan_id', 'recipe_id', 'course_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meal_plan_recipe_mappings', function (Blueprint $table) {
            $table->dropForeign(['meal_plan_id']);
            $table->dropForeign(['recipe_id']);
            $table->dropForeign(['course_type_id']);
        });

        Schema::dropIfExists('meal_plan_recipe_mappings');
    }
};
