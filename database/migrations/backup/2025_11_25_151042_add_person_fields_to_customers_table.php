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
            // Tipo de pessoa: F (Física - padrão) ou J (Jurídica)
            $table->char('person_type', 1)->default('F')->after('cpf_cnpj');
            
            // Campos Pessoa Física
            $table->date('birth_date')->nullable()->after('person_type');
            
            // Campos Pessoa Jurídica
            $table->string('fantasy_name')->nullable()->after('birth_date'); // Nome fantasia
            $table->string('state_registration')->nullable()->after('fantasy_name'); // Inscrição Estadual (IE)
            
            // Email para envio de NF-e (opcional, usa email principal se vazio)
            $table->string('nfe_email')->nullable()->after('email');
            
            // Telefone comercial (adicional ao celular)
            $table->string('phone_commercial')->nullable()->after('phone');
            
            // Tipo de contribuinte ICMS (para B2B futuro)
            // 1 = Contribuinte ICMS, 2 = Isento, 9 = Não contribuinte (padrão B2C)
            $table->tinyInteger('taxpayer_type')->default(9)->after('state_registration');
            
            // Índices para buscas
            $table->index('person_type');
            $table->index('taxpayer_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['person_type']);
            $table->dropIndex(['taxpayer_type']);
            
            $table->dropColumn([
                'person_type',
                'birth_date',
                'fantasy_name',
                'state_registration',
                'nfe_email',
                'phone_commercial',
                'taxpayer_type'
            ]);
        });
    }
};
