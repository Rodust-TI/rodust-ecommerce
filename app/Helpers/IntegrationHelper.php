<?php

namespace App\Helpers;

class IntegrationHelper
{
    /**
     * Retorna o modo atual de operação das integrações
     * 
     * @return string 'sandbox' ou 'production'
     */
    public static function getMode(): string
    {
        return config('services.melhor_envio.mode', 'sandbox');
    }

    /**
     * Verifica se está em modo sandbox
     * 
     * @return bool
     */
    public static function isSandbox(): bool
    {
        return self::getMode() === 'sandbox';
    }

    /**
     * Retorna as credenciais do Mercado Pago conforme o modo atual
     * 
     * @return array ['public_key' => string, 'access_token' => string]
     */
    public static function getMercadoPagoCredentials(): array
    {
        $mode = self::getMode();
        $suffix = $mode === 'sandbox' ? '_sandbox' : '_prod';

        return [
            'public_key' => config("services.mercadopago.public_key{$suffix}"),
            'access_token' => config("services.mercadopago.access_token{$suffix}"),
            'mode' => $mode,
        ];
    }

    /**
     * Retorna as credenciais do Melhor Envio conforme o modo atual
     * 
     * @return array ['client_id' => string, 'client_secret' => string, 'base_url' => string]
     */
    public static function getMelhorEnvioCredentials(): array
    {
        $mode = self::getMode();
        $suffix = $mode === 'sandbox' ? '_sandbox' : '_prod';

        return [
            'client_id' => config("services.melhor_envio.client_id{$suffix}"),
            'client_secret' => config("services.melhor_envio.client_secret{$suffix}"),
            'base_url' => config("services.melhor_envio.{$mode}_url"),
            'origin_cep' => config('services.melhor_envio.origin_cep'),
            'mode' => $mode,
        ];
    }

    /**
     * Define o modo de operação das integrações
     * 
     * @param string $mode 'sandbox' ou 'production'
     * @return void
     * @throws \InvalidArgumentException
     */
    public static function setMode(string $mode): void
    {
        if (!in_array($mode, ['sandbox', 'production'])) {
            throw new \InvalidArgumentException("Modo inválido: {$mode}. Use 'sandbox' ou 'production'.");
        }

        // Atualizar o .env
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);

        $envContent = preg_replace(
            '/INTEGRATIONS_MODE=.*/',
            "INTEGRATIONS_MODE={$mode}",
            $envContent
        );

        file_put_contents($envPath, $envContent);

        // Limpar cache de configuração
        \Artisan::call('config:clear');
    }
}
