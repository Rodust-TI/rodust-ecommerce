<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Migration consolidada - configurações do Melhor Envio
     */
    public function up(): void
    {
        Schema::create('melhor_envio_settings', function (Blueprint $table) {
            $table->id();
            $table->string('client_id');
            $table->string('client_secret');
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->text('bearer_token')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('sandbox_mode')->default(true);
            $table->string('origin_postal_code', 9)->nullable()->comment('CEP de origem');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('melhor_envio_settings');
    }
};

