<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class BlingGetToken extends Command
{
    protected $signature = 'bling:get-token {code : Authorization code from Bling callback URL}';
    
    protected $description = 'Exchange authorization code for OAuth2 access token';

    public function handle()
    {
        $code = $this->argument('code');

        $this->info('🔄 Trocando código por token OAuth2...');

        try {
            $response = Http::asForm()->post(config('services.bling.base_url') . '/oauth/token', [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => config('services.bling.redirect_uri'),
            ])->withBasicAuth(
                config('services.bling.client_id'),
                config('services.bling.client_secret')
            );

            if ($response->failed()) {
                $this->error('❌ Erro ao obter token: ' . $response->body());
                return 1;
            }

            $data = $response->json();

            // Salvar token no cache
            Cache::put('bling_access_token', $data['access_token'], now()->addSeconds($data['expires_in']));
            Cache::put('bling_refresh_token', $data['refresh_token'], now()->addDays(30));

            $this->newLine();
            $this->info('✅ Token obtido com sucesso!');
            $this->newLine();
            $this->line('📝 <fg=yellow>Access Token:</> ' . $data['access_token']);
            $this->line('🔄 <fg=yellow>Refresh Token:</> ' . $data['refresh_token']);
            $this->line('⏰ <fg=yellow>Expira em:</> ' . $data['expires_in'] . ' segundos (' . round($data['expires_in'] / 3600, 1) . ' horas)');
            $this->newLine();
            $this->line('💾 Token salvo no cache Redis automaticamente!');
            $this->newLine();
            $this->info('🚀 Agora você pode executar: php artisan bling:validate --token=' . $data['access_token']);

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Erro: ' . $e->getMessage());
            return 1;
        }
    }
}
