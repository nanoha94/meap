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
        Schema::create('groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('group_size');
            $table->timestamps();
        });

        Schema::create('group_user_mappings', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('users', 'id')->cascadeOnDelete();
            $table->foreignUuid('group_id')->constrained('groups', 'id');
            $table->primary(['user_id', 'group_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // group_user_mappingテーブルの外部キー制約を削除
        Schema::table('group_user_mappings', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['group_id']);
        });

        Schema::dropIfExists('groups');
        Schema::dropIfExists('group_user_mappings');
    }
};
