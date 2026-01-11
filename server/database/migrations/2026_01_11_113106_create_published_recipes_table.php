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
        Schema::create('published_recipes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('recipe_id')->unique()->constrained('recipes', 'id')->cascadeOnDelete();
            $table->foreignUuid('owner_user_id')->constrained('users', 'id')->cascadeOnDelete();
            $table->foreignUuid('thumbnail_image_id')->nullable()->constrained('images', 'id')->cascadeOnDelete();
            $table->string('name');
            $table->integer('serving_count');
            $table->timestamp('published_at');
            $table->timestamp('last_published_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('published_recipes', function (Blueprint $table) {
            $table->dropForeign(['recipe_id']);
            $table->dropForeign(['owner_user_id']);
            $table->dropForeign(['thumbnail_image_id']);
        });
        Schema::dropIfExists('published_recipes');
    }
};
