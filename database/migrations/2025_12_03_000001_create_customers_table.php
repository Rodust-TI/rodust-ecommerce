<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Migration consolidada - estrutura final da tabela customers
     * Consolida todas as alterações feitas durante desenvolvimento
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();
            
            // Verificação de email
            $table->string('verification_token')->nullable();
            $table->timestamp('verification_token_expires_at')->nullable();
            
            // Reset de senha
            $table->string('password_reset_token', 64)->nullable()->unique();
            $table->timestamp('password_reset_token_expires_at')->nullable();
            $table->boolean('must_reset_password')->default(false);
            
            // Contato
            $table->string('phone')->nullable();
            $table->string('phone_commercial')->nullable();
            
            // Documentos
            $table->string('cpf', 11)->nullable()->unique();
            $table->string('cnpj', 14)->nullable()->unique();
            
            // Tipo de pessoa
            $table->enum('person_type', ['F', 'J'])->default('F');
            $table->date('birth_date')->nullable();
            
            // Pessoa Jurídica
            $table->string('fantasy_name')->nullable();
            $table->string('state_registration')->nullable();
            $table->string('state_uf', 2)->nullable();
            $table->tinyInteger('taxpayer_type')->default(9);
            
            // Email NF-e
            $table->string('nfe_email')->nullable();
            
            // Integração Bling
            $table->unsignedBigInteger('bling_id')->nullable()->unique();
            $table->timestamp('bling_synced_at')->nullable();
            
            // Campos de endereço antigos (mantidos temporariamente para compatibilidade)
            $table->string('zipcode')->nullable();
            $table->string('address')->nullable();
            $table->string('number')->nullable();
            $table->string('complement')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('email');
            $table->index('cpf');
            $table->index('cnpj');
            $table->index('bling_id');
            $table->index('person_type');
            $table->index('taxpayer_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};

