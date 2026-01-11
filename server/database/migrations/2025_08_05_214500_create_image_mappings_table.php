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
        Schema::create('image_mappings', function (Blueprint $table) {
            $table->foreignUuid('image_id')->constrained('images', 'id')->cascadeOnDelete();
            $table->foreignUuid('group_id')->constrained('groups', 'id')->cascadeOnDelete();
            $table->string('related_model');
            $table->uuid('related_id');
            $table->string('image_type');
            $table->integer('order');
            // ポリモーフィックリレーション: related_modelとrelated_idの組み合わせで異なるモデル（Recipe, RecipeStep, Userなど）を参照
            // related_modelが主キーに含まれる理由: 異なるモデルで同じIDが存在する可能性があるため（例: RecipeのIDとRecipeStepのIDが同じ値）
            $table->primary(['image_id', 'related_model', 'related_id', 'group_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('image_mappings', function (Blueprint $table) {
            $table->dropForeign(['image_id']);
            $table->dropForeign(['group_id']);
        });

        Schema::dropIfExists('image_mappings');
    }
};
