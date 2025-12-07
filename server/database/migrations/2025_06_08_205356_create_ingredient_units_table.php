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
        Schema::create('ingredient_units', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->enum('position', ['prefix', 'suffix']);
            $table->boolean('requires_quantity');
            $table->integer('order');
            $table->foreignUuid('group_id')->constrained('groups', 'id')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ingredient_units', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
        });

        Schema::dropIfExists('ingredient_units');
    }
};
