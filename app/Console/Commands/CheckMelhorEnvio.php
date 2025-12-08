<?php

namespace App\Console\Commands;

use App\Models\MelhorEnvioSetting;
use App\Services\MelhorEnvioService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CheckMelhorEnvio extends Command
{
    protected $signature = 'melhorenvio:check';
    protected $description = 'Verificar configurações e testar comunicação com Melhor Envio';

    public function handle()
    {
        $this->info('=== Verificação do Melhor Envio ===');
        $this->newLine();

        // Verificar configurações no banco
        $settings = MelhorEnvioSetting::getSettings();

        if (!$settings) {
            $this->error('❌ Nenhuma configuração encontrada no banco de dados!');
            $this->warn('Configure via: /api/melhor-envio/settings ou via .env');
            return 1;
        }

        $this->info('📋 Configurações encontradas:');
        $this->line('   Sandbox Mode: ' . ($settings->sandbox_mode ? 'SIM' : 'NÃO'));
        $this->line('   CEP Origem: ' . ($settings->origin_postal_code ?? 'NÃO CONFIGURADO'));
        $this->line('   Client ID: ' . ($settings->client_id ? '✅ Configurado' : '❌ Não configurado'));
        $this->line('   Client Secret: ' . ($settings->client_secret ? '✅ Configurado' : '❌ Não configurado'));
        $this->line('   Bearer Token: ' . ($settings->bearer_token ? '✅ Configurado' : '❌ Não configurado'));
        $this->line('   Access Token: ' . ($settings->access_token ? '✅ Configurado' : '❌ Não configurado'));
        $this->line('   Refresh Token: ' . ($settings->refresh_token ? '✅ Configurado' : '❌ Não configurado'));
        
        if ($settings->expires_at) {
            $isExpired = $settings->isTokenExpired();
            $this->line('   Token Expira: ' . $settings->expires_at->format('Y-m-d H:i:s') . 
                       ($isExpired ? ' ❌ EXPIRADO' : ' ✅ Válido'));
        }

        $this->newLine();

        // Verificar qual método de autenticação está sendo usado
        $this->info('🔐 Método de Autenticação:');
        if ($settings->bearer_token) {
            $this->warn('   ⚠️  Usando Bearer Token (método direto)');
            $this->warn('   Recomendado: Usar OAuth (client_id + client_secret) para renovação automática');
        } elseif ($settings->access_token) {
            $this->info('   ✅ Usando OAuth (renovação automática)');
        } else {
            $this->error('   ❌ Nenhum token disponível!');
            $this->warn('   Configure a autenticação OAuth primeiro');
            return 1;
        }

        $this->newLine();

        // Testar comunicação
        $this->info('🧪 Testando comunicação com API...');
        
        try {
            $service = new MelhorEnvioService();
            $token = $this->getTokenForTest($settings);
            
            if (!$token) {
                $this->error('❌ Não foi possível obter token para teste');
                return 1;
            }

            $baseUrl = $settings->sandbox_mode
                ? 'https://sandbox.melhorenvio.com.br/api/v2'
                : 'https://melhorenvio.com.br/api/v2';

            // Testar endpoint simples (me)
            $response = Http::withToken($token)
                ->timeout(10)
                ->get($baseUrl . '/me');

            if ($response->successful()) {
                $data = $response->json();
                $this->info('✅ Comunicação OK!');
                $this->line('   Nome: ' . ($data['name'] ?? 'N/A'));
                $this->line('   Email: ' . ($data['email'] ?? 'N/A'));
            } else {
                $this->error('❌ Erro na comunicação:');
                $this->error('   Status: ' . $response->status());
                $this->error('   Resposta: ' . $response->body());
                
                if ($response->status() === 401) {
                    $this->warn('   Token inválido ou expirado. Tente renovar o token.');
                }
                
                return 1;
            }

            $this->newLine();

            // Testar cálculo de frete (se CEP origem estiver configurado)
            if ($settings->origin_postal_code) {
                $this->info('📦 Testando cálculo de frete...');
                
                try {
                    $options = $service->calculateShipping(
                        '01310100', // CEP de teste (Av. Paulista)
                        [
                            [
                                'quantity' => 1,
                                'weight' => 0.3,
                                'height' => 2,
                                'width' => 11,
                                'length' => 16,
                            ]
                        ]
                    );

                    $this->info('✅ Cálculo de frete OK!');
                    $this->line('   Opções encontradas: ' . count($options));
                    
                    if (count($options) > 0) {
                        $this->line('   Primeira opção: ' . $options[0]['name'] . ' - R$ ' . number_format($options[0]['price'], 2, ',', '.'));
                    }
                } catch (\Exception $e) {
                    $this->error('❌ Erro ao calcular frete:');
                    $this->error('   ' . $e->getMessage());
                    return 1;
                }
            } else {
                $this->warn('⚠️  CEP de origem não configurado. Pulando teste de cálculo.');
            }

            $this->newLine();
            $this->info('✅ Todas as verificações passaram!');

        } catch (\Exception $e) {
            $this->error('❌ Erro ao testar comunicação:');
            $this->error('   ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function getTokenForTest(MelhorEnvioSetting $settings): ?string
    {
        // Se tem bearer token, usar ele
        if ($settings->bearer_token) {
            return $settings->bearer_token;
        }

        // Se tem access token, usar ele (mesmo se expirado para testar)
        if ($settings->access_token) {
            return $settings->access_token;
        }

        return null;
    }
}
