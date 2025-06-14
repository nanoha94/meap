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
        Schema::create('dish_ingredient_mappings', function (Blueprint $table) {
            $table->foreignUuid('dish_id')->constrained('dishes', 'id')->cascadeOnDelete();
            $table->foreignUuid('ingredient_id')->constrained('ingredients', 'id')->cascadeOnDelete();
            $table->foreignUuid('unit_id')->constrained('ingredient_units', 'id')->cascadeOnDelete();
            $table->float('quantity');
            $table->primary(['dish_id', 'ingredient_id', 'unit_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dish_ingredient_mappings', function (Blueprint $table) {
            $table->dropForeign(['dish_id']);
            $table->dropForeign(['ingredient_id']);
            $table->dropForeign(['unit_id']);
        });
        Schema::dropIfExists('dish_ingredient_mappings');
    }
};
