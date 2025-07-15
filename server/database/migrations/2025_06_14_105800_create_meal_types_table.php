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
        Schema::create('meal_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('group_id')->constrained('groups', 'id')->cascadeOnDelete();
            $table->foreignUuid('color_id')->constrained('colors', 'id')->cascadeOnDelete();
            $table->string('name');
            $table->integer('order');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meal_types', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropForeign(['color_id']);
        });

        Schema::dropIfExists('meal_types');
    }
};
