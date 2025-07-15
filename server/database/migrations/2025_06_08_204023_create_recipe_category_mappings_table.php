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
        Schema::create('recipe_category_mappings', function (Blueprint $table) {
            $table->foreignUuid('recipe_id')->constrained('recipes', 'id')->cascadeOnDelete();
            $table->foreignUuid('category_id')->constrained('recipe_categories', 'id')->cascadeOnDelete();
            $table->primary(['recipe_id', 'category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipe_category_mappings', function (Blueprint $table) {
            $table->dropForeign(['recipe_id']);
            $table->dropForeign(['category_id']);
        });

        Schema::dropIfExists('recipe_category_mappings');
    }
};
