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
        if (! Schema::hasTable('savings_deposits')) {
            Schema::create('savings_deposits', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('savings_goal_id')->constrained('savings_goals');
                $table->foreignUuid('wallet_id')->nullable()->constrained('wallets');
                $table->decimal('amount', 15, 2);
                $table->date('deposit_date');
                $table->string('notes', 255)->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('savings_deposits');
    }
};
