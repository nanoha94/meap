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
        Schema::create('meal_recipe_mappings', function (Blueprint $table) {
            $table->foreignUuid('meal_id')->constrained('meals', 'id')->cascadeOnDelete();
            $table->foreignUuid('recipe_id')->constrained('recipes', 'id')->cascadeOnDelete();
            $table->primary(['meal_id', 'recipe_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meal_recipe_mappings', function (Blueprint $table) {
            $table->dropForeign(['meal_id']);
            $table->dropForeign(['recipe_id']);
        });

        Schema::dropIfExists('meal_recipe_mappings');
    }
};
