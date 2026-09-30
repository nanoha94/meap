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
            $table->string('plan')->default('free');
            $table->unsignedInteger('ai_monthly_remaining')->default(3);
            $table->timestamp('ai_usage_reset_at')->nullable();
            $table->unsignedInteger('ai_pack_remaining')->default(0);
            $table->string('square_customer_id')->nullable()->index();
            $table->string('square_card_id')->nullable();
            $table->string('pm_type')->nullable();
            $table->string('pm_last_four', 4)->nullable();
            $table->unsignedTinyInteger('pm_exp_month')->nullable();
            $table->unsignedSmallInteger('pm_exp_year')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
