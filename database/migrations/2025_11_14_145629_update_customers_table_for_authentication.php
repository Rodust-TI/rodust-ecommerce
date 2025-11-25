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
            // Campos de autenticação
            $table->string('password')->after('email');
            $table->rememberToken()->after('password');
            $table->timestamp('email_verified_at')->nullable()->after('email');
            
            // Tornar CPF obrigatório e único
            $table->string('cpf_cnpj', 14)->nullable(false)->unique()->change();
            
            // Remover campos de endereço (migrar para customer_addresses)
            // Não dropar agora para não perder dados - fazer migration de dados depois
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['password', 'remember_token', 'email_verified_at']);
        });
    }
};
