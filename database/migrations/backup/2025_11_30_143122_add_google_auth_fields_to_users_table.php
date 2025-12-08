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
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('email');
            $table->string('avatar')->nullable()->after('google_id');
            $table->boolean('must_reset_password')->default(false)->after('avatar');
            $table->boolean('synced_from_bling')->default(false)->after('must_reset_password');
            $table->timestamp('last_sync_at')->nullable()->after('synced_from_bling');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'google_id',
                'avatar',
                'must_reset_password',
                'synced_from_bling',
                'last_sync_at',
            ]);
        });
    }
};
