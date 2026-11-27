<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('wishlists')) {
            DB::table('wishlists')
                ->whereNull('delay_until')
                ->update(['delay_until' => now()->toDateString()]);

            Schema::table('wishlists', function (Blueprint $table) {
                $table->date('delay_until')->nullable(false)->change();
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
                $table->date('delay_until')->nullable()->change();
            });
        }
    }
};

