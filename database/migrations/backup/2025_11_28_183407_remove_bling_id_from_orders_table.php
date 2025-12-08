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
        Schema::table('orders', function (Blueprint $table) {
            // Verificar se a coluna existe antes de remover
            if (Schema::hasColumn('orders', 'bling_id')) {
                // Remover índice primeiro se existir
                $indexExists = collect(DB::select("SHOW INDEX FROM orders WHERE Key_name = 'orders_bling_id_index'"))->isNotEmpty();
                if ($indexExists) {
                    $table->dropIndex(['bling_id']);
                }
                // Remover coluna bling_id (redundante com bling_order_number)
                $table->dropColumn('bling_id');
            }
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
