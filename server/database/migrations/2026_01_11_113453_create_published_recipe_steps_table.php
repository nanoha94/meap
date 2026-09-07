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
        Schema::create('published_recipe_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('published_recipe_id')->constrained('published_recipes', 'id')->cascadeOnDelete();
            $table->foreignUuid('image_id')->nullable()->constrained('images', 'id')->cascadeOnDelete();
            $table->string('instruction');
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('published_recipe_steps', function (Blueprint $table) {
            $table->dropForeign(['published_recipe_id']);
            $table->dropForeign(['image_id']);
        });
        Schema::dropIfExists('published_recipe_steps');
    }
};
