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
        Schema::create('meals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('meal_plan_id')->constrained('meal_plans', 'id')->cascadeOnDelete();
            $table->foreignUuid('category_id')->constrained('meal_categories', 'id')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meals', function (Blueprint $table) {
            $table->dropForeign(['meal_plan_id']);
            $table->dropForeign(['category_id']);
        });

        Schema::dropIfExists('meals');
    }
};
