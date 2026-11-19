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
        if (!Schema::hasTable('wishlists')) {
            Schema::create('wishlists', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('user_id')->constrained('users');
                $table->foreignUuid('savings_goal_id')->nullable()->constrained('savings_goals');
                $table->string('item_name', 200);
                $table->text('purchase_url')->nullable();
                $table->decimal('price', 15, 2);
                $table->decimal('saved_amount', 15, 2)->default(0);
                $table->unsignedInteger('delay_days')->default(7);
                $table->date('delay_until')->nullable();
                $table->enum('status', ['delayed', 'pending_decision', 'bought', 'cancelled'])->default('delayed');
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
        Schema::dropIfExists('wishlists');
    }
};

