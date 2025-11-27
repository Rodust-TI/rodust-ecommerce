<?php

namespace App\Console\Commands;

use App\Models\MelhorEnvioSetting;
use Illuminate\Console\Command;

class ShowMelhorEnvioSettings extends Command
{
    protected $signature = 'melhorenvio:show';
    protected $description = 'Mostrar configurações atuais do Melhor Envio';

    public function handle()
    {
        $settings = MelhorEnvioSetting::first();

        if (!$settings) {
            $this->error('❌ Nenhuma configuração encontrada no banco.');
            $this->newLine();
            $this->info('Execute: php artisan melhorenvio:setup');
            return 1;
        }

        $this->info('📋 Configurações do Melhor Envio');
        $this->newLine();

        $this->table(
            ['Campo', 'Valor'],
            [
                ['Client ID', $settings->client_id ?: '(vazio)'],
                ['Client Secret', $settings->client_secret ? str_repeat('*', 20) . substr($settings->client_secret, -10) : '(vazio)'],
                ['Bearer Token', $settings->bearer_token ? '✅ Configurado (' . strlen($settings->bearer_token) . ' chars)' : '❌ Não configurado'],
                ['Access Token (OAuth)', $settings->access_token ? '✅ Configurado' : '❌ Não configurado'],
                ['CEP Origem', $settings->origin_postal_code],
                ['Modo', $settings->sandbox_mode ? '🧪 Sandbox (Testes)' : '🚀 Produção'],
                ['Token Expira', $settings->expires_at ? $settings->expires_at->format('d/m/Y H:i') : 'N/A'],
            ]
        );

        $this->newLine();
        
        if ($settings->bearer_token) {
            $this->info('✅ Usando Bearer Token (método direto)');
        } elseif ($settings->access_token) {
            $this->info('✅ Usando OAuth2 (Client ID + Secret)');
        } else {
            $this->warn('⚠️  Nenhum método de autenticação configurado!');
        }

        return 0;
    }
}
