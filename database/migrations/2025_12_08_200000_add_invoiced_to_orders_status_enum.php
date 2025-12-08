<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adicionar 'invoiced' ao ENUM de status da tabela orders
     */
    public function up(): void
    {
        // MySQL não permite alterar ENUM diretamente, precisamos recriar a coluna
        DB::statement("ALTER TABLE `orders` MODIFY COLUMN `status` ENUM('pending', 'paid', 'processing', 'invoiced', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover 'invoiced' do ENUM (voltar ao estado anterior)
        DB::statement("ALTER TABLE `orders` MODIFY COLUMN `status` ENUM('pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending'");
    }
};

