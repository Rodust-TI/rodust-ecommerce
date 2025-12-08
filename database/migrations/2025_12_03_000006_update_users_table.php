<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Migration consolidada - atualiza tabela users com campos adicionais
     * Mantém estrutura base do Laravel e adiciona campos customizados
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Google OAuth
            $table->string('google_id')->nullable()->unique()->after('email');
            $table->string('avatar')->nullable()->after('google_id');
            
            // Reset de senha
            $table->boolean('must_reset_password')->default(false)->after('password');
            $table->string('password_reset_token', 64)->nullable()->unique()->after('password');
            $table->timestamp('password_reset_token_expires_at')->nullable()->after('password_reset_token');
            
            // Integração Bling
            $table->boolean('synced_from_bling')->default(false)->after('must_reset_password');
            $table->timestamp('last_sync_at')->nullable()->after('synced_from_bling');
            $table->unsignedBigInteger('bling_id')->nullable()->unique()->after('id');
            
            // Dados adicionais
            $table->string('cpf', 14)->nullable()->after('email');
            $table->string('phone', 20)->nullable()->after('cpf');
            
            // Tornar password nullable (para login via Google)
            $table->string('password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'google_id',
                'avatar',
                'must_reset_password',
                'password_reset_token',
                'password_reset_token_expires_at',
                'synced_from_bling',
                'last_sync_at',
                'bling_id',
                'cpf',
                'phone',
            ]);
            
            // Reverter password para NOT NULL
            $table->string('password')->nullable(false)->change();
        });
    }
};

