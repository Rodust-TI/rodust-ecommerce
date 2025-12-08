<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Migration consolidada - estrutura final da tabela customer_addresses
     */
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            
            // Tipo de endereço (mantido para compatibilidade, deprecated)
            $table->enum('type', ['shipping', 'billing', 'invoice'])->default('shipping');
            
            // Flags de tipo de endereço
            $table->boolean('is_shipping')->default(false)->comment('Endereço de entrega');
            $table->boolean('is_billing')->default(false)->comment('Endereço de cobrança');
            
            // Dados do endereço
            $table->string('label')->nullable()->comment('Ex: Casa, Trabalho, Escritório');
            $table->string('recipient_name')->nullable()->comment('Nome de quem recebe');
            $table->string('zipcode', 9);
            $table->string('address');
            $table->string('number', 10);
            $table->string('complement')->nullable();
            $table->string('neighborhood');
            $table->string('city');
            $table->string('state', 2);
            $table->string('country', 2)->default('BR');
            
            // Dados para faturamento (quando type = invoice)
            $table->string('invoice_cpf_cnpj', 18)->nullable();
            $table->string('invoice_name')->nullable();
            $table->string('invoice_ie')->nullable();
            $table->string('invoice_im')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index(['customer_id', 'type']);
            $table->index('is_shipping');
            $table->index('is_billing');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};

