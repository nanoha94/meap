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
            $table->foreignUuid('inviter_user_id')->constrained('users', 'id')->cascadeOnDelete();
            $table->string('token')->unique();
            $table->string('token_lookup', 8);
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('token_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // invitation_tokensテーブルの外部キー制約を削除
        Schema::table('invitation_tokens', function (Blueprint $table) {
            $table->dropForeign(['inviter_user_id']);
        });
        Schema::dropIfExists('invitation_tokens');
    }
};
