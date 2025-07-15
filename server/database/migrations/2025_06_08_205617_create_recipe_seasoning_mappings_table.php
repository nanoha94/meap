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
        Schema::create('recipe_seasoning_mappings', function (Blueprint $table) {
            $table->foreignUuid('recipe_id')->constrained('recipes', 'id')->cascadeOnDelete();
            $table->foreignUuid('seasoning_id')->constrained('seasonings', 'id')->cascadeOnDelete();
            $table->foreignUuid('unit_id')->constrained('seasoning_units', 'id')->cascadeOnDelete();
            $table->float('quantity');
            $table->primary(['recipe_id', 'seasoning_id', 'unit_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipe_seasoning_mappings', function (Blueprint $table) {
            $table->dropForeign(['recipe_id']);
            $table->dropForeign(['seasoning_id']);
            $table->dropForeign(['unit_id']);
        });

        Schema::dropIfExists('recipe_seasoning_mappings');
    }
};
