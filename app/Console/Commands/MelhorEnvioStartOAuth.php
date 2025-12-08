<?php

namespace App\Console\Commands;

use App\Services\MelhorEnvioService;
use Illuminate\Console\Command;

class MelhorEnvioStartOAuth extends Command
{
    protected $signature = 'melhorenvio:start-oauth';
    protected $description = 'Iniciar fluxo OAuth do Melhor Envio (se usar Client ID + Secret)';

    public function handle()
    {
        $this->info('=== Iniciar Fluxo OAuth Melhor Envio ===');
        $this->newLine();

        try {
            $service = new MelhorEnvioService();
            
            // Gerar URL de autorização
            $state = \Illuminate\Support\Str::random(40);
            $redirectUri = url('/api/melhor-envio/oauth/callback');
            
            // Se usar UltraHook, usar a URL pública
            $ultrahookUrl = 'https://sanozukez-melhorenvio-oauth.ultrahook.com';
            $redirectUri = $ultrahookUrl . '/api/melhor-envio/oauth/callback';
            
            $authUrl = $service->getAuthorizationUrl($redirectUri, $state);
            
            $this->info('✅ URL de autorização gerada!');
            $this->newLine();
            $this->warn('⚠️  IMPORTANTE: Configure esta URL no painel Melhor Envio:');
            $this->line('   ' . $redirectUri);
            $this->newLine();
            $this->info('🔗 Acesse esta URL no navegador para autorizar:');
            $this->line('   ' . $authUrl);
            $this->newLine();
            $this->info('📋 Passos:');
            $this->line('   1. Configure a URL de callback no painel Melhor Envio');
            $this->line('   2. Acesse a URL acima no navegador');
            $this->line('   3. Clique em "Autorizar" no Melhor Envio');
            $this->line('   4. Você será redirecionado e o token será salvo automaticamente');
            $this->newLine();
            
            // Tentar abrir no navegador (Windows)
            if (PHP_OS_FAMILY === 'Windows') {
                $this->info('🌐 Tentando abrir no navegador...');
                exec("start $authUrl");
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Erro: ' . $e->getMessage());
            $this->newLine();
            $this->warn('💡 Certifique-se de que:');
            $this->line('   - Client ID e Client Secret estão configurados');
            $this->line('   - UltraHook está rodando (se usar)');
            return 1;
        }

        return 0;
    }
}
