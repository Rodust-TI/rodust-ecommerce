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
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->string('service')->unique(); // 'bling', 'mercadopago', 'melhorenvio'
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->json('credentials')->nullable(); // Outros dados (client_id, etc)
            $table->json('config')->nullable(); // Configurações adicionais
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
            
            $table->index('service');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
