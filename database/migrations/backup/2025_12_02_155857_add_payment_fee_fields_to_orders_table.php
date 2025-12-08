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
            // Taxas e valores do Mercado Pago
            $table->decimal('payment_fee', 10, 2)->nullable()->comment('Taxa cobrada pelo gateway de pagamento');
            $table->decimal('net_amount', 10, 2)->nullable()->comment('Valor líquido recebido (total - taxa)');
            $table->json('payment_details')->nullable()->comment('Detalhes completos do pagamento (JSON)');
            $table->integer('installments')->default(1)->comment('Número de parcelas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_fee', 'net_amount', 'payment_details', 'installments']);
        });
    }
};
