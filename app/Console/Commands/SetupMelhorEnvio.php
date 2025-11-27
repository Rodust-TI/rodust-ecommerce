<?php

namespace App\Console\Commands;

use App\Models\MelhorEnvioSetting;
use Illuminate\Console\Command;

class SetupMelhorEnvio extends Command
{
    protected $signature = 'melhorenvio:setup 
                            {--client-id= : Client ID do Melhor Envio}
                            {--client-secret= : Client Secret do Melhor Envio}
                            {--bearer-token= : Bearer Token (token de acesso direto)}
                            {--cep= : CEP de origem dos envios}
                            {--sandbox : Usar ambiente de testes (sandbox)}';

    protected $description = 'Configurar credenciais do Melhor Envio';

    public function handle()
    {
        $this->info('🚀 Configurando Melhor Envio...');
        $this->newLine();

        // Perguntar qual método de autenticação
        $authMethod = $this->choice(
            'Qual método de autenticação você quer usar?',
            ['Bearer Token (Recomendado - mais simples)', 'OAuth2 (Client ID + Secret)'],
            0
        );

        $useBearerToken = str_contains($authMethod, 'Bearer Token');

        if ($useBearerToken) {
            // Método Bearer Token (simples)
            $bearerToken = $this->option('bearer-token') ?? $this->ask('Bearer Token (token de acesso)');
            $cep = $this->option('cep') ?? $this->ask('CEP de Origem (apenas números)');
            $sandbox = $this->option('sandbox') ?? $this->confirm('Usar ambiente de testes (sandbox)?', true);

            // Validar CEP
            $cep = preg_replace('/\D/', '', $cep);
            if (strlen($cep) !== 8) {
                $this->error('CEP inválido! Deve conter 8 dígitos.');
                return 1;
            }

            // Salvar configurações
            $settings = MelhorEnvioSetting::getSettings();

            if ($settings) {
                $settings->update([
                    'bearer_token' => $bearerToken,
                    'origin_postal_code' => $cep,
                    'sandbox_mode' => $sandbox,
                ]);
                $this->info('✅ Configurações atualizadas!');
            } else {
                MelhorEnvioSetting::create([
                    'client_id' => '',
                    'client_secret' => '',
                    'bearer_token' => $bearerToken,
                    'origin_postal_code' => $cep,
                    'sandbox_mode' => $sandbox,
                ]);
                $this->info('✅ Configurações criadas!');
            }

            $this->newLine();
            $this->info('📋 Configurações salvas:');
            $this->table(
                ['Configuração', 'Valor'],
                [
                    ['Bearer Token', str_repeat('*', strlen($bearerToken) - 20) . substr($bearerToken, -20)],
                    ['CEP Origem', substr($cep, 0, 5) . '-' . substr($cep, 5)],
                    ['Modo', $sandbox ? 'Sandbox (Teste)' : 'Produção'],
                ]
            );

            $this->newLine();
            $this->info('✅ Pronto! Você já pode usar o Melhor Envio.');
            $this->line('Teste com: php artisan tinker');
            $this->line('>>> $service = new \App\Services\MelhorEnvioService();');
            $this->line('>>> $service->calculateShipping("01310100", [["quantity" => 1, "weight" => 0.5]]);');

        } else {
            // Método OAuth2 (complexo)
            $clientId = $this->option('client-id') ?? $this->ask('Client ID');
            $clientSecret = $this->option('client-secret') ?? $this->ask('Client Secret');
            $cep = $this->option('cep') ?? $this->ask('CEP de Origem (apenas números)');
            $sandbox = $this->option('sandbox') ?? $this->confirm('Usar ambiente de testes (sandbox)?', true);

            // Validar CEP
            $cep = preg_replace('/\D/', '', $cep);
            if (strlen($cep) !== 8) {
                $this->error('CEP inválido! Deve conter 8 dígitos.');
                return 1;
            }

            // Salvar configurações
            $settings = MelhorEnvioSetting::getSettings();

            if ($settings) {
                $settings->update([
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'origin_postal_code' => $cep,
                    'sandbox_mode' => $sandbox,
                ]);
                $this->info('✅ Configurações atualizadas!');
            } else {
                MelhorEnvioSetting::create([
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'origin_postal_code' => $cep,
                    'sandbox_mode' => $sandbox,
                ]);
                $this->info('✅ Configurações criadas!');
            }

            $this->newLine();
            $this->info('📋 Configurações salvas:');
            $this->table(
                ['Configuração', 'Valor'],
                [
                    ['Client ID', $clientId],
                    ['Client Secret', str_repeat('*', strlen($clientSecret) - 4) . substr($clientSecret, -4)],
                    ['CEP Origem', substr($cep, 0, 5) . '-' . substr($cep, 5)],
                    ['Modo', $sandbox ? 'Sandbox (Teste)' : 'Produção'],
                ]
            );

            $this->newLine();
            $this->warn('⚠️  Próximo passo: Autenticar com OAuth2');
            $this->info('Configure no Melhor Envio:');
            $this->line('  • URL de Redirecionamento: ' . url('/api/melhor-envio/oauth/callback'));
            $this->line('  • URL de Webhook: ' . url('/api/melhor-envio/webhook'));
        }

        return 0;
    }
}
