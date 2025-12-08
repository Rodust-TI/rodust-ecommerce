<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Migration consolidada - estrutura final da tabela orders
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('order_number')->unique();
            
            // Status
            $table->enum('status', ['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled'])->default('pending');
            
            // Valores
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('shipping', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            
            // Pagamento
            $table->string('payment_method')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('payment_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->decimal('payment_fee', 10, 2)->nullable()->comment('Taxa do gateway');
            $table->decimal('net_amount', 10, 2)->nullable()->comment('Valor líquido recebido');
            $table->json('payment_details')->nullable()->comment('Detalhes completos do pagamento');
            $table->integer('installments')->default(1)->comment('Número de parcelas');
            
            // Nota Fiscal
            $table->string('invoice_number')->nullable();
            $table->string('invoice_key')->nullable()->comment('Chave de acesso NF-e');
            $table->string('invoice_pdf_url')->nullable();
            $table->timestamp('invoice_issued_at')->nullable();
            
            // Endereço de entrega
            $table->json('shipping_address')->nullable();
            $table->string('shipping_method_name')->nullable();
            $table->string('shipping_carrier')->nullable()->comment('Transportadora (ex: Jadlog, Correios)');
            $table->string('tracking_code')->nullable()->comment('Código de rastreamento');
            
            // Integração Bling
            $table->string('bling_order_number')->nullable()->unique();
            $table->timestamp('last_bling_sync')->nullable();
            $table->timestamp('bling_synced_at')->nullable();
            
            // Observações
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('order_number');
            $table->index('status');
            $table->index('customer_id');
            $table->index('bling_order_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

