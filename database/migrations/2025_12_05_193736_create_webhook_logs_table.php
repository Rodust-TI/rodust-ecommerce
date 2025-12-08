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
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('source')->index(); // 'bling', 'mercadopago', etc
            $table->string('event_id')->nullable()->index(); // ID único do evento
            $table->string('event_type')->nullable()->index(); // 'order.updated', 'stock.updated', etc
            $table->string('resource')->nullable(); // 'order', 'product', 'stock', etc
            $table->string('action')->nullable(); // 'created', 'updated', 'deleted'
            $table->enum('status', ['received', 'processing', 'success', 'error'])->default('received')->index();
            $table->text('payload')->nullable(); // JSON completo do webhook
            $table->text('response')->nullable(); // Resposta enviada ao Bling
            $table->integer('response_code')->nullable(); // HTTP status code
            $table->text('error_message')->nullable(); // Mensagem de erro se houver
            $table->json('metadata')->nullable(); // Dados extras (product_id, order_id, etc)
            $table->timestamp('processed_at')->nullable(); // Quando foi processado
            $table->timestamps();
            
            // Índices para busca rápida
            $table->index(['source', 'created_at']);
            $table->index(['event_type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
