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
        Schema::create('dish_category_mappings', function (Blueprint $table) {
            $table->foreignUuid('dish_id')->constrained('dishes', 'id')->cascadeOnDelete();
            $table->foreignUuid('category_id')->constrained('dish_categories', 'id')->cascadeOnDelete();
            $table->primary(['dish_id', 'category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dishes_category_mappings', function (Blueprint $table) {
            $table->dropForeign(['dish_id']);
            $table->dropForeign(['category_id']);
        });

        Schema::dropIfExists('dishes_category_mappings');
    }
};
