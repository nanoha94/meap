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
        Schema::create('recipes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('group_id')->constrained('groups', 'id')->cascadeOnDelete();
            $table->foreignUuid('owner_user_id')->constrained('users', 'id')->cascadeOnDelete();
            // published_recipe_idの外部キー制約は、published_recipesテーブル作成後に別のマイグレーションで追加
            $table->uuid('published_recipe_id')->nullable();
            $table->string('name');
            $table->string('url')->nullable();
            $table->string('memo')->nullable();
            $table->integer('serving_count')->nullable();
            $table->integer('cooking_time')->nullable();
            $table->enum('status', ['limited', 'public'])->default('limited');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropForeign(['owner_user_id']);
            // published_recipe_idの外部キー制約は別のマイグレーションで削除される
        });

        Schema::dropIfExists('recipes');
    }
};
