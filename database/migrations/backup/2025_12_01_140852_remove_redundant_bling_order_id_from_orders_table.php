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
        Schema::table('orders', function (Blueprint $table) {
            // Remover bling_order_id (redundante com bling_order_number que é mais descritivo)
            if (Schema::hasColumn('orders', 'bling_order_id')) {
                $table->dropColumn('bling_order_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Recriar caso precise reverter (não recomendado)
            $table->string('bling_order_id')->nullable()->after('order_number');
        });
    }
};
