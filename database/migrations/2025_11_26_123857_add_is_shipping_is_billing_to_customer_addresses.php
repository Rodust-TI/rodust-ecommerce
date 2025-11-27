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
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->boolean('is_shipping')->default(false)->after('type');
            $table->boolean('is_billing')->default(false)->after('is_shipping');
        });
        
        // Migrar dados existentes: type='shipping' -> is_shipping=true, type='billing' -> is_billing=true
        DB::statement("UPDATE customer_addresses SET is_shipping = 1 WHERE type = 'shipping'");
        DB::statement("UPDATE customer_addresses SET is_billing = 1 WHERE type = 'billing'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->dropColumn(['is_shipping', 'is_billing']);
        });
    }
};
