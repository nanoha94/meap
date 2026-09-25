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
        Schema::table('groups', function (Blueprint $table) {
            // PAY.JP Customer ID（cus_...）。API の billingStatus とは別の外部 ID
            $table->string('payjp_customer_id')->nullable()->index();
            // 登録カードのブランド（visa 等）。API レスポンスの pmType に対応（Cashier 由来の列名）
            $table->string('pm_type')->nullable();
            // 登録カード番号の下4桁。API レスポンスの pmLastFour に対応
            $table->string('pm_last_four', 4)->nullable();
            // 登録カードの有効期限（月）。API レスポンスの pmExpMonth に対応
            $table->unsignedTinyInteger('pm_exp_month')->nullable();
            // 登録カードの有効期限（年）。API レスポンスの pmExpYear に対応
            $table->unsignedSmallInteger('pm_exp_year')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropIndex([
                'payjp_customer_id',
            ]);

            $table->dropColumn([
                'payjp_customer_id',
                'pm_type',
                'pm_last_four',
                'pm_exp_month',
                'pm_exp_year',
            ]);
        });
    }
};
