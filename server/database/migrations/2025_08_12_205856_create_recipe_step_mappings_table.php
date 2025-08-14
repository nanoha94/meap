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
        Schema::create('recipe_step_mappings', function (Blueprint $table) {
            $table->foreignUuid('recipe_id')->constrained('recipes', 'id')->cascadeOnDelete();
            $table->foreignUuid('step_id')->constrained('recipe_steps', 'id')->cascadeOnDelete();
            $table->integer('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipe_step_mappings', function (Blueprint $table) {
            $table->dropForeign(['recipe_id']);
            $table->dropForeign(['step_id']);
        });
        Schema::dropIfExists('recipe_step_mappings');
    }
};
