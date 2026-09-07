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
        Schema::create('shopping_item_tag_mappings', function (Blueprint $table) {
            $table->foreignUuid('item_id')->constrained('shopping_items', 'id')->cascadeOnDelete();
            $table->foreignUuid('tag_id')->constrained('shopping_tags', 'id')->cascadeOnDelete();
            $table->primary(['item_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shopping_item_tag_mappings', function (Blueprint $table) {
            $table->dropForeign(['item_id']);
            $table->dropForeign(['tag_id']);
        });

        Schema::dropIfExists('shopping_item_tag_mappings');
    }
};
