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
        if (Schema::hasTable('wishlists')) {
            Schema::table('wishlists', function (Blueprint $table) {
                $table->text('notes')->nullable()->after('delay_until');
                $table->enum('status', ['delayed', 'pending_decision', 'bought', 'cancelled', 'archived'])->default('delayed')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('wishlists')) {
            Schema::table('wishlists', function (Blueprint $table) {
                $table->dropColumn('notes');
                $table->enum('status', ['delayed', 'pending_decision', 'bought', 'cancelled'])->default('delayed')->change();
            });
        }
    }
};

