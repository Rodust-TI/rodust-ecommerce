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
            // Remover índice primeiro
            $table->dropIndex(['bling_id']);
            // Remover coluna bling_id (redundante com bling_order_number)
            $table->dropColumn('bling_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Recriar coluna caso precise reverter
            $table->string('bling_id')->nullable()->unique();
            $table->index('bling_id');
        });
    }
};
