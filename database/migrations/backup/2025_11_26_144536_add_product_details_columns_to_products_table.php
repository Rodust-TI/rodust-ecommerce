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
        Schema::table('products', function (Blueprint $table) {
            // Dimensões físicas para cálculo de frete
            $table->decimal('width', 10, 2)->nullable()->after('price')->comment('Largura em cm');
            $table->decimal('height', 10, 2)->nullable()->after('width')->comment('Altura em cm');
            $table->decimal('length', 10, 2)->nullable()->after('height')->comment('Comprimento em cm');
            $table->decimal('weight', 10, 3)->nullable()->after('length')->comment('Peso em kg');
            
            // Informações comerciais
            $table->string('brand')->nullable()->after('weight')->comment('Marca/Fabricante');
            $table->decimal('promotional_price', 10, 2)->nullable()->after('price')->comment('Preço promocional');
            $table->boolean('free_shipping')->default(false)->after('promotional_price')->comment('Frete grátis');
            
            // Múltiplas imagens (JSON array de URLs)
            $table->json('images')->nullable()->after('image')->comment('Array de URLs de imagens adicionais');
            
            // Categoria do Bling
            $table->string('bling_category_id')->nullable()->after('bling_id')->comment('ID da categoria no Bling');
            
            // Controle de sincronização
            $table->timestamp('last_sync_at')->nullable()->after('updated_at')->comment('Última sincronização com Bling');
            $table->string('sync_status')->nullable()->after('last_sync_at')->comment('Status: synced, pending, error');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'width',
                'height',
                'length',
                'weight',
                'brand',
                'promotional_price',
                'free_shipping',
                'images',
                'bling_category_id',
                'last_sync_at',
                'sync_status',
            ]);
        });
    }
};
