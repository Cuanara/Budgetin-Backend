<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    if (!Schema::hasColumn('transactions', 'wallet_id')) {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignUuid('wallet_id')
                ->nullable()
                ->after('user_id')
                ->constrained('wallets');
        });
    }

    DB::statement("
        UPDATE transactions
        INNER JOIN wallets ON transactions.user_id = wallets.user_id
        SET transactions.wallet_id = wallets.id
        WHERE transactions.wallet_id IS NULL
    ");
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
             $table->dropForeign(['wallet_id']);
             $table->dropColumn('wallet_id');
        });
    }
};
