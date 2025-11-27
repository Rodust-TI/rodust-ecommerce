<?php

namespace App\Console\Commands;

use App\Services\MelhorEnvioService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MelhorEnvioGetAuthUrl extends Command
{
    protected $signature = 'melhorenvio:auth-url {--ngrok-url= : URL do ngrok (ex: https://abc.ngrok-free.dev)}';

    protected $description = 'Gerar URL para autorização OAuth do Melhor Envio';

    public function handle()
    {
        $ngrokUrl = $this->option('ngrok-url');

        if (!$ngrokUrl) {
            $ngrokUrl = $this->ask('Digite a URL do ngrok (ex: https://abc.ngrok-free.dev)');
        }

        // Remove trailing slash
        $ngrokUrl = rtrim($ngrokUrl, '/');

        try {
            $service = new MelhorEnvioService();
            $state = Str::random(40);
            
            $redirectUri = $ngrokUrl . '/api/melhor-envio/oauth/callback';
            $authUrl = $service->getAuthorizationUrl($redirectUri, $state);

            $this->newLine();
            $this->info('🔐 URL de Autorização OAuth gerada com sucesso!');
            $this->newLine();
            
            $this->line('📋 PASSO A PASSO:');
            $this->line('  1. Copie a URL abaixo');
            $this->line('  2. Cole no navegador');
            $this->line('  3. Faça login no Melhor Envio (se necessário)');
            $this->line('  4. Clique em "Autorizar"');
            $this->line('  5. Você será redirecionado de volta');
            $this->newLine();
            
            $this->warn('🔗 URL DE AUTORIZAÇÃO:');
            $this->line($authUrl);
            $this->newLine();
            
            $this->info('✅ Callback configurado: ' . $redirectUri);
            
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Erro: ' . $e->getMessage());
            return 1;
        }
    }
}
