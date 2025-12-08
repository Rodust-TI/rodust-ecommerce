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
            // Adicionar campos separados
            $table->string('cpf', 11)->nullable()->after('email');
            $table->string('cnpj', 14)->nullable()->after('cpf');
            
            // Migrar dados existentes
            // Se cpf_cnpj tem 11 dígitos → cpf
            // Se cpf_cnpj tem 14 dígitos → cnpj
        });
        
        // Migrar dados do campo antigo para os novos
        DB::statement("
            UPDATE customers 
            SET cpf = REPLACE(REPLACE(REPLACE(cpf_cnpj, '.', ''), '-', ''), '/', '')
            WHERE LENGTH(REPLACE(REPLACE(REPLACE(cpf_cnpj, '.', ''), '-', ''), '/', '')) = 11
        ");
        
        DB::statement("
            UPDATE customers 
            SET cnpj = REPLACE(REPLACE(REPLACE(cpf_cnpj, '.', ''), '-', ''), '/', '')
            WHERE LENGTH(REPLACE(REPLACE(REPLACE(cpf_cnpj, '.', ''), '-', ''), '/', '')) = 14
        ");
        
        Schema::table('customers', function (Blueprint $table) {
            // Remover campo antigo
            $table->dropColumn('cpf_cnpj');
            
            // Adicionar índices
            $table->unique('cpf', 'customers_cpf_unique');
            $table->unique('cnpj', 'customers_cnpj_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Recriar campo antigo
            $table->string('cpf_cnpj', 18)->nullable()->after('email');
            
            // Remover índices
            $table->dropUnique('customers_cpf_unique');
            $table->dropUnique('customers_cnpj_unique');
        });
        
        // Migrar de volta (pega o primeiro disponível)
        DB::statement("UPDATE customers SET cpf_cnpj = cpf WHERE cpf IS NOT NULL");
        DB::statement("UPDATE customers SET cpf_cnpj = cnpj WHERE cnpj IS NOT NULL AND cpf IS NULL");
        
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['cpf', 'cnpj']);
        });
    }
};
