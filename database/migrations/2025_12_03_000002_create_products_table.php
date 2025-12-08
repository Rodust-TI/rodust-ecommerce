<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Migration consolidada - estrutura final da tabela products
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            
            // Preços
            $table->decimal('price', 10, 2);
            $table->decimal('promotional_price', 10, 2)->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            
            // Estoque
            $table->integer('stock')->default(0);
            $table->boolean('active')->default(true);
            
            // Imagens
            $table->string('image')->nullable();
            $table->json('images')->nullable();
            
            // Dimensões para frete
            $table->decimal('width', 10, 2)->nullable()->comment('Largura em cm');
            $table->decimal('height', 10, 2)->nullable()->comment('Altura em cm');
            $table->decimal('length', 10, 2)->nullable()->comment('Comprimento em cm');
            $table->decimal('weight', 10, 3)->nullable()->comment('Peso em kg');
            
            // Informações comerciais
            $table->string('brand')->nullable();
            $table->boolean('free_shipping')->default(false);
            
            // Integrações
            $table->string('bling_id')->nullable()->unique();
            $table->string('bling_category_id')->nullable();
            $table->unsignedBigInteger('wordpress_post_id')->nullable();
            $table->timestamp('bling_synced_at')->nullable();
            
            // Controle de sincronização
            $table->timestamp('last_sync_at')->nullable();
            $table->string('sync_status')->nullable()->comment('Status: synced, pending, error');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('sku');
            $table->index('bling_id');
            $table->index('wordpress_post_id');
            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

