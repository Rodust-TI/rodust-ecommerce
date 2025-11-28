# Guia de Integrações Externas

Este documento explica como gerenciar as integrações com serviços externos (Mercado Pago, Melhor Envio) no projeto Rodust.

## Modo de Operação Unificado

Todas as integrações compartilham um modo de operação comum, controlado pela variável `INTEGRATIONS_MODE` no arquivo `.env`:

```bash
# Modo sandbox (testes)
INTEGRATIONS_MODE=sandbox

# Modo produção
INTEGRATIONS_MODE=production
```

**Importante:** Você precisa configurar apenas **UMA** variável para controlar todas as integrações!

## Estrutura de Credenciais

### Mercado Pago

```bash
# Credenciais Sandbox (Testes)
MERCADOPAGO_PUBLIC_KEY_SANDBOX=TEST-1c7102d1-ecd4-4b00-ac5b-5c1279c661b1
MERCADOPAGO_ACCESS_TOKEN_SANDBOX=TEST-2725469665099447-112711-4825e27dc3863fb61bccb2e51dbed292-158424236

# Credenciais Produção
MERCADOPAGO_PUBLIC_KEY_PROD=APP_USR-xxxx
MERCADOPAGO_ACCESS_TOKEN_PROD=APP_USR-xxxx
```

### Melhor Envio

```bash
MELHOR_ENVIO_ORIGIN_CEP=13400710

# OAuth2 - Sandbox (Testes)
MELHOR_ENVIO_CLIENT_ID_SANDBOX=7552
MELHOR_ENVIO_CLIENT_SECRET_SANDBOX=pEe4w3t4uWXlgwT9klHtVD8lnammzb4x123XU8bS

# OAuth2 - Produção
MELHOR_ENVIO_CLIENT_ID_PROD=15782
MELHOR_ENVIO_CLIENT_SECRET_PROD=BXFwSxZoabMZJcVynlk37HXYgpC8C9FzgLBsEQuf
```

## Como Usar no Código

### Helper Class: IntegrationHelper

Use a classe helper para obter as credenciais corretas automaticamente:

```php
use App\Helpers\IntegrationHelper;

// Verificar modo atual
$mode = IntegrationHelper::getMode(); // 'sandbox' ou 'production'
$isSandbox = IntegrationHelper::isSandbox(); // true ou false

// Obter credenciais do Mercado Pago
$mpCredentials = IntegrationHelper::getMercadoPagoCredentials();
// Retorna: [
//   'public_key' => 'TEST-xxxx' ou 'APP_USR-xxxx',
//   'access_token' => 'TEST-xxxx' ou 'APP_USR-xxxx',
//   'mode' => 'sandbox' ou 'production'
// ]

// Obter credenciais do Melhor Envio
$meCredentials = IntegrationHelper::getMelhorEnvioCredentials();
// Retorna: [
//   'client_id' => '7552' ou '15782',
//   'client_secret' => 'xxxx',
//   'base_url' => 'https://sandbox.melhorenvio.com.br/api/v2' ou 'https://melhorenvio.com.br/api/v2',
//   'origin_cep' => '13400710',
//   'mode' => 'sandbox' ou 'production'
// ]
```

### Exemplo Prático - Mercado Pago

```php
use App\Helpers\IntegrationHelper;
use MercadoPago\SDK;

class MercadoPagoService
{
    protected $client;

    public function __construct()
    {
        $credentials = IntegrationHelper::getMercadoPagoCredentials();
        
        SDK::setAccessToken($credentials['access_token']);
        
        // Usar public_key no frontend
        $this->publicKey = $credentials['public_key'];
        
        logger()->info('Mercado Pago inicializado', [
            'mode' => $credentials['mode']
        ]);
    }
}
```

### Exemplo Prático - Melhor Envio

```php
use App\Helpers\IntegrationHelper;
use Illuminate\Support\Facades\Http;

class MelhorEnvioService
{
    protected $baseUrl;
    protected $credentials;

    public function __construct()
    {
        $this->credentials = IntegrationHelper::getMelhorEnvioCredentials();
        $this->baseUrl = $this->credentials['base_url'];
        
        logger()->info('Melhor Envio inicializado', [
            'mode' => $this->credentials['mode'],
            'base_url' => $this->baseUrl
        ]);
    }

    public function calculateShipping(array $data)
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/me/shipment/calculate", array_merge($data, [
            'from' => ['postal_code' => $this->credentials['origin_cep']]
        ]));

        return $response->json();
    }
}
```

## Mudando de Sandbox para Produção

### Método 1: Manualmente (arquivo .env)

```bash
# Abra o arquivo .env e altere:
INTEGRATIONS_MODE=production
```

### Método 2: Programaticamente (via código)

```php
use App\Helpers\IntegrationHelper;

// Alterar para produção
IntegrationHelper::setMode('production');

// Alterar para sandbox
IntegrationHelper::setMode('sandbox');
```

**Nota:** O método programático atualiza o arquivo `.env` e limpa o cache de configuração automaticamente.

## Checklist para Ir para Produção

Antes de alterar `INTEGRATIONS_MODE=production`, certifique-se de:

- [ ] Configurar `MERCADOPAGO_PUBLIC_KEY_PROD` e `MERCADOPAGO_ACCESS_TOKEN_PROD` no `.env`
- [ ] Configurar `MELHOR_ENVIO_CLIENT_ID_PROD` e `MELHOR_ENVIO_CLIENT_SECRET_PROD` no `.env`
- [ ] Testar todas as funcionalidades em sandbox antes
- [ ] Configurar webhook do Mercado Pago com URL de produção
- [ ] Configurar callback do Melhor Envio com URL de produção
- [ ] Revisar logs de erro antes da mudança
- [ ] Ter plano de rollback (voltar para sandbox se necessário)
- [ ] Monitorar logs após a mudança

## Acessando Configurações Diretamente

Se você preferir acessar as configurações diretamente (não recomendado):

```php
// Mercado Pago
$mode = config('services.mercadopago.mode'); // 'sandbox' ou 'production'
$publicKeySandbox = config('services.mercadopago.public_key_sandbox');
$publicKeyProd = config('services.mercadopago.public_key_prod');

// Melhor Envio
$mode = config('services.melhor_envio.mode');
$clientIdSandbox = config('services.melhor_envio.client_id_sandbox');
$clientIdProd = config('services.melhor_envio.client_id_prod');
```

**Recomendação:** Use sempre o `IntegrationHelper` para obter as credenciais corretas automaticamente!

## Painel de Administração (Em Desenvolvimento)

Em breve, você poderá gerenciar todas essas credenciais através de painéis web:

- `/admin/integrations/mercadopago` - Gerenciar credenciais Mercado Pago
- `/admin/integrations/melhorenvio` - Gerenciar credenciais Melhor Envio
- `/admin` - Dashboard geral de integrações

Os links já estão disponíveis no WordPress em **Rodust Ecommerce → Configurações → Integrações Externas**.

## Segurança

**IMPORTANTE:**

- ✅ Credenciais ficam APENAS no Laravel (arquivo `.env`)
- ✅ WordPress NÃO armazena credenciais no banco de dados
- ✅ Webhook URLs devem usar HTTPS em produção
- ❌ NUNCA commite o arquivo `.env` no Git
- ❌ NUNCA exponha credenciais de produção em logs
- ❌ NUNCA use credenciais de produção em ambiente de desenvolvimento

## Suporte

Em caso de dúvidas ou problemas:

1. Verifique os logs: `storage/logs/laravel.log`
2. Teste em sandbox antes de ir para produção
3. Consulte a documentação oficial:
   - [Mercado Pago API](https://www.mercadopago.com.br/developers/pt/docs)
   - [Melhor Envio API](https://docs.melhorenvio.com.br/)
