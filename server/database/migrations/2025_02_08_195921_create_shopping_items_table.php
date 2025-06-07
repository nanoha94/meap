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
        Schema::create('shopping_tags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('group_id')->constrained('groups', 'id')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('shopping_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('group_id')->constrained('groups', 'id')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('shopping_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('group_id')->constrained('groups', 'id')->cascadeOnDelete();
            $table->foreignUuid('category_id')->constrained('shopping_categories', 'id')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_checked')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('shopping_item_tag_mappings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shopping_item_id')->constrained('shopping_items', 'id')->cascadeOnDelete();
            $table->foreignUuid('shopping_tag_id')->constrained('shopping_tags', 'id')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shopping_item_tag_mappings', function (Blueprint $table) {
            $table->dropForeign(['shopping_item_id']);
            $table->dropForeign(['shopping_tag_id']);
        });

        Schema::table('shopping_items', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropForeign(['category_id']);
        });

        Schema::table('shopping_categories', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
        });

        Schema::table('shopping_tags', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
        });

        Schema::dropIfExists('shopping_item_tag_mappings');
        Schema::dropIfExists('shopping_items');
        Schema::dropIfExists('shopping_categories');
        Schema::dropIfExists('shopping_tags');
    }
};
