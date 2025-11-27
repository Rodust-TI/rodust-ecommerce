<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Alterar enum para permitir apenas shipping, billing ou NULL
        DB::statement("ALTER TABLE customer_addresses MODIFY COLUMN type ENUM('shipping', 'billing') NULL");
        
        // Converter endereços invoice existentes para NULL (adicionais)
        DB::table('customer_addresses')
            ->where('type', 'invoice')
            ->update(['type' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE customer_addresses MODIFY COLUMN type ENUM('shipping', 'billing', 'invoice') NOT NULL");
    }
};
