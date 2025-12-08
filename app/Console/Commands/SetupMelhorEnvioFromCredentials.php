<?php

namespace App\Console\Commands;

use App\Models\MelhorEnvioSetting;
use Illuminate\Console\Command;

class SetupMelhorEnvioFromCredentials extends Command
{
    protected $signature = 'melhorenvio:setup-from-credentials';
    protected $description = 'Configurar Melhor Envio com credenciais do arquivo de documentação';

    public function handle()
    {
        $this->info('=== Configurando Melhor Envio (Sandbox) ===');
        $this->newLine();

        // Credenciais do arquivo MELHOR-ENVIO-CREDENTIALS.md
        $settings = MelhorEnvioSetting::updateOrCreate(
            ['id' => 1], // Usar ID 1 para garantir que é único
            [
                'client_id' => '7552',
                'client_secret' => 'pEe4w3t4uWXlgwT9klHtVD8lnammzb4x123XU8bS',
                'bearer_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiI5NTYiLCJqdGkiOiJkNzBjODc3OTM5OGE0NTQ2NzI4NWNlMzFjZTVlM2ZiMjU2ZGFiMGM0NTUzZWFhYWZkMDg3NTVjMmMzNDkxMmEwYjRiNDNmZDJkZDVlMzYzZiIsImlhdCI6MTc2NDI1MjgwNC44MDIxMTIsIm5iZiI6MTc2NDI1MjgwNC44MDIxMTUsImV4cCI6MTc5NTc4ODgwNC43ODg4OTIsInN1YiI6IjljNWY5MGM5LTU4NTMtNDM2MS05NTBkLTAwYzlhNDExNWJhZiIsInNjb3BlcyI6WyJjYXJ0LXJlYWQiLCJjYXJ0LXdyaXRlIiwiY29tcGFuaWVzLXJlYWQiLCJjb21wYW5pZXMtd3JpdGUiLCJjb3Vwb25zLXJlYWQiLCJjb3Vwb25zLXdyaXRlIiwibm90aWZpY2F0aW9ucy1yZWFkIiwib3JkZXJzLXJlYWQiLCJwcm9kdWN0cy1yZWFkIiwicHJvZHVjdHMtZGVzdHJveSIsInByb2R1Y3RzLXdyaXRlIiwicHVyY2hhc2VzLXJlYWQiLCJzaGlwcGluZy1jYWxjdWxhdGUiLCJzaGlwcGluZy1jYW5jZWwiLCJzaGlwcGluZy1jaGVja291dCIsInNoaXBwaW5nLWNvbXBhbmllcyIsInNoaXBwaW5nLWdlbmVyYXRlIiwic2hpcHBpbmctcHJldmlldyIsInNoaXBwaW5nLXByaW50Iiwic2hpcHBpbmctc2hhcmUiLCJzaGlwcGluZy10cmFja2luZyIsImVjb21tZXJjZS1zaGlwcGluZyIsInRyYW5zYWN0aW9ucy1yZWFkIiwidXNlcnMtcmVhZCIsInVzZXJzLXdyaXRlIiwid2ViaG9va3MtcmVhZCIsIndlYmhvb2tzLXdyaXRlIiwid2ViaG9va3MtZGVsZXRlIiwidGRlYWxlci13ZWJob29rIl19.NwY_wTw0iBUF766b6ZojTvqOfQbuS6fdNtAMDe5DUPZ3FiKsjVKmKz4Acn5tFtRezAmZ9K7fqo5vocccv3FPnlkRtlULzj87xJyiVGIqMxdD8wcWQV3kDj0vk4bgL-EEvTck0-B3SCFS5zoK4sB3bK-pxrIH6ZT9UIFqi9KcC9IWunbYXJOJJ7AgUrTLoRPGLn-PiIkz_QteBGLuEz9j-tuefsKD-AlyRT_-phjtUI59aay0TB_hm56jHtMyHx2GJ4bccshZQWAzq6lgm23iat92dJaSJuQCPZZLEswFQAX3Sae9PbV4WgobIeVe5x4PVJFd4hhkVhA1XwkxoExzag_N3z4RCNR7jiYzIQLMJLmDUVdUIp5ILM0Qq_64PGuYJTQrh_L_Re7B9U_wfuP6is_w8i9niBxM4tbEs2BUhd0MRTMXK_0gyZSsMe_HaiJTLF9kPggtn_zSpuvCuJOweBmy_VdyRA7uK07fYxziVa6bemdp-oh7IJNlccTdAeguD8zBdyNpjrp7yTrdlTbyakizJBJm1JfIJLklUNksN9IM9RfEV1nCOGXJfjyCOucTP40c95hBOs0IdMhFGjhHF5uuW83LEiwt1q4BZVv16Y3Iqd2oI_eg9Du1KlJV4zJ3FBSlCf9t_LBKGlE2pNpAf9eVqG-UOufYMMXIjeT3vy4',
                'sandbox_mode' => true,
                'origin_postal_code' => '13400710',
            ]
        );

        $this->info('✅ Configuração salva com sucesso!');
        $this->newLine();
        $this->info('📋 Configurações:');
        $this->line('   Client ID: ' . $settings->client_id);
        $this->line('   Client Secret: ' . ($settings->client_secret ? '✅ Configurado' : '❌ Não configurado'));
        $this->line('   Bearer Token: ' . ($settings->bearer_token ? '✅ Configurado' : '❌ Não configurado'));
        $this->line('   Sandbox Mode: ' . ($settings->sandbox_mode ? 'SIM' : 'NÃO'));
        $this->line('   CEP Origem: ' . $settings->origin_postal_code);
        $this->newLine();

        $this->info('🧪 Testando comunicação...');
        try {
            $service = new \App\Services\MelhorEnvioService();
            $options = $service->calculateShipping(
                '01310100', // CEP de teste
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

            $this->info('✅ Comunicação OK!');
            $this->line('   Opções encontradas: ' . count($options));
            if (count($options) > 0) {
                $this->line('   Primeira opção: ' . $options[0]['name'] . ' - R$ ' . number_format($options[0]['price'], 2, ',', '.'));
            }
        } catch (\Exception $e) {
            $this->error('❌ Erro ao testar: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
