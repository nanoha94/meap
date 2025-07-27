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
        Schema::create('recipe_ingredient_mappings', function (Blueprint $table) {
            $table->foreignUuid('recipe_id')->constrained('recipes', 'id')->cascadeOnDelete();
            $table->foreignUuid('ingredient_id')->constrained('ingredients', 'id')->cascadeOnDelete();
            $table->foreignUuid('unit_id')->constrained('ingredient_units', 'id')->cascadeOnDelete();
            $table->float('quantity')->nullable();
            $table->integer('order')->default(0);
            $table->primary(['recipe_id', 'ingredient_id', 'unit_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipe_ingredient_mappings', function (Blueprint $table) {
            $table->dropForeign(['recipe_id']);
            $table->dropForeign(['ingredient_id']);
            $table->dropForeign(['unit_id']);
        });
        Schema::dropIfExists('recipe_ingredient_mappings');
    }
};
