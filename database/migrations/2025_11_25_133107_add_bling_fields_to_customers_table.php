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
        Schema::table('customers', function (Blueprint $table) {
            $table->string('bling_id')->nullable()->unique()->after('email_verified_at');
            $table->timestamp('bling_synced_at')->nullable()->after('bling_id');
            
            $table->index('bling_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['bling_id']);
            $table->dropColumn(['bling_id', 'bling_synced_at']);
        });
    }
};
