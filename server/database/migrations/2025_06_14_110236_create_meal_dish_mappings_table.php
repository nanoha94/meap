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
        Schema::create('meal_dish_mappings', function (Blueprint $table) {
            $table->foreignUuid('meal_id')->constrained('meals', 'id')->cascadeOnDelete();
            $table->foreignUuid('dish_id')->constrained('dishes', 'id')->cascadeOnDelete();
            $table->foreignUuid('dish_role_id')->constrained('dish_roles', 'id')->cascadeOnDelete();
            $table->primary(['meal_id', 'dish_id', 'dish_role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meal_dish_mappings', function (Blueprint $table) {
            $table->dropForeign(['meal_id']);
            $table->dropForeign(['dish_id']);
            $table->dropForeign(['dish_role_id']);
        });

        Schema::dropIfExists('meal_dish_mappings');
    }
};
