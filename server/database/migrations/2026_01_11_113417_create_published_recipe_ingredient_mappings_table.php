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
        Schema::create('published_recipe_ingredient_mappings', function (Blueprint $table) {
            $table->foreignUuid('published_recipe_id')->constrained('published_recipes', 'id')->cascadeOnDelete();
            $table->foreignUuid('ingredient_id')->constrained('published_ingredients', 'id')->cascadeOnDelete();
            $table->foreignUuid('category_id')->constrained('published_ingredient_categories', 'id')->cascadeOnDelete();
            $table->float('quantity')->nullable();
            $table->string('unit_name');
            $table->enum('unit_position', ['prefix', 'suffix']);
            $table->integer('order');
            $table->primary(['published_recipe_id', 'ingredient_id', 'category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('published_recipe_ingredient_mappings', function (Blueprint $table) {
            $table->dropForeign(['published_recipe_id']);
            $table->dropForeign(['ingredient_id']);
            $table->dropForeign(['category_id']);
        });
        Schema::dropIfExists('published_recipe_ingredient_mappings');
    }
};
