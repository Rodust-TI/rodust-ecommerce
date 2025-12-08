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
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            
            // Tipo de endereço: shipping (entrega), billing (cobrança), invoice (faturamento)
            $table->enum('type', ['shipping', 'billing', 'invoice'])->default('shipping');
            
            // Dados do endereço
            $table->string('label')->nullable(); // Ex: "Casa", "Trabalho", "Escritório"
            $table->string('recipient_name')->nullable(); // Nome de quem recebe (pode ser diferente do titular)
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
            $table->string('invoice_name')->nullable(); // Razão social ou nome completo
            $table->string('invoice_ie')->nullable(); // Inscrição estadual
            $table->string('invoice_im')->nullable(); // Inscrição municipal
            
            // Controle
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index(['customer_id', 'type']);
            $table->index('is_default');
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
