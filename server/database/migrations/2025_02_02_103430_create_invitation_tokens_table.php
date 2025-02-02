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
        Schema::create('invitation_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('inviter_id')->constrained('users', 'id')->cascadeOnDelete();
            $table->string('token')->unique();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // invitation_tokensテーブルの外部キー制約を削除
        Schema::table('invitation_tokens', function (Blueprint $table) {
            $table->dropForeign(['inviter_id']);
        });
        Schema::dropIfExists('invitation_tokens');
    }
};
