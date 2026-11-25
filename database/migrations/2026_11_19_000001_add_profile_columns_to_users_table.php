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
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('occupation', 100)->nullable()->after('name');
                $table->string('phone_number', 25)->nullable()->after('occupation');
                $table->string('avatar', 255)->nullable()->after('phone_number');
                $table->boolean('notification_budget')->default(true)->after('remember_token');
                $table->boolean('notification_wishlist')->default(true)->after('notification_budget');
                $table->boolean('notification_saving')->default(true)->after('notification_wishlist');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn([
                    'occupation',
                    'phone_number',
                    'avatar',
                    'notification_budget',
                    'notification_wishlist',
                    'notification_saving',
                ]);
            });
        }
    }
};
