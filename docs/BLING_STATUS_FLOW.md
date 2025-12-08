# Fluxo de Status de Pedidos - Integração Bling

## 📋 Visão Geral

Este documento descreve o fluxo completo de gerenciamento de status de pedidos entre o sistema Laravel e o Bling ERP v3.

## 🔄 Fluxo Atual Implementado

### 1. Criação do Pedido

Quando um cliente realiza um pedido:

```
Cliente faz pedido → PaymentController
                   ↓
        OrderCreationService cria pedido local
                   ↓
             Status inicial: "pending"
                   ↓
        BlingOrderService cria pedido no Bling
                   ↓
        Bling retorna número do pedido
                   ↓
        Salva bling_order_number no banco
```

**Arquivos envolvidos:**
- `app/Http/Controllers/Api/PaymentController.php`
- `app/Services/Order/OrderCreationService.php`
- `app/Services/Bling/BlingOrderService.php`

### 2. Sincronização de Status

#### Método Automático (Webhooks)

O Bling envia notificações quando o status de um pedido muda:

```
Bling atualiza status do pedido
            ↓
Webhook POST /webhook
            ↓
WebhookController.handleOrderWebhook()
            ↓
BlingStatusService.mapBlingStatusToInternal()
            ↓
Atualiza order.status no banco local
```

**Arquivos envolvidos:**
- `app/Http/Controllers/Api/WebhookController.php`
- `app/Services/Bling/BlingStatusService.php`

#### Método Manual (Comando Artisan)

Sincronização sob demanda via comando:

```bash
php artisan bling:sync-orders --limit=50
```

```
BlingSyncOrderStatuses command
            ↓
BlingOrderService.syncAllPendingOrders()
            ↓
Para cada pedido pendente:
  - Busca dados no Bling API
  - BlingStatusService mapeia status
  - Atualiza banco local
```

**Arquivos envolvidos:**
- `app/Console/Commands/BlingSyncOrderStatuses.php`
- `app/Services/Bling/BlingOrderService.php`

## 📊 Mapeamento de Status

### Status Internos (Enums)

```php
pending     → Pendente / Aguardando
processing  → Em Processamento
invoiced    → Faturado (NF emitida)
shipped     → Enviado
delivered   → Entregue
cancelled   → Cancelado
```

**Arquivo:** `app/Enums/OrderStatus.php`

### Obtenção Dinâmica dos Status do Bling

O sistema agora busca dinamicamente os status do Bling:

```
1. GET /situacoes/modulos
   → Descobre ID do módulo "Vendas"
   
2. GET /situacoes?idModulo={ID}
   → Lista todos os status personalizados
   
3. Cache por 24 horas
   → Evita requisições repetidas
```

**Comando para visualizar:**
```bash
php artisan bling:fetch-statuses
```

**Arquivo:** `app/Services/Bling/BlingStatusService.php`

## 🛠️ Arquitetura de Serviços

### BlingStatusService

Responsabilidades:
- ✅ Descobrir ID do módulo de Vendas
- ✅ Obter lista de situações do Bling
- ✅ Mapear status do Bling → status interno
- ✅ Cachear dados por 24 horas

Métodos principais:
```php
getSalesModuleId(): ?int
getSalesStatuses(): array
getStatusName(int $statusId): string
mapBlingStatusToInternal(array $blingStatus): string
clearCache(): void
```

### BlingOrderService

Responsabilidades:
- ✅ Criar pedido no Bling
- ✅ Buscar pedido no Bling por número
- ✅ Sincronizar status de um pedido
- ✅ Sincronizar todos os pedidos pendentes

Métodos principais:
```php
createOrder(Order $order): array
getOrder(string $blingOrderNumber): ?array
syncOrderStatus(Order $order): bool
syncAllPendingOrders(int $limit = 50): array
```

### BlingV3Adapter

Novos métodos adicionados:
```php
getModules(): array                    // GET /situacoes/modulos
getStatuses(int $moduleId): array      // GET /situacoes?idModulo={ID}
getOrderById(string $orderId): ?array  // GET /pedidos/vendas/{id}
getOrders(array $filters = []): array  // GET /pedidos/vendas
```

## 🚀 Comandos Artisan

### 1. Buscar Status do Bling

```bash
php artisan bling:fetch-statuses
```

Exibe:
- ID do módulo de Vendas
- Lista completa de situações cadastradas no Bling
- Mapeamento para status internos
- Tabela formatada com cores

Opções:
```bash
php artisan bling:fetch-statuses --clear-cache
```

### 2. Sincronizar Pedidos

```bash
php artisan bling:sync-orders
```

Sincroniza até 50 pedidos pendentes por padrão.

Opções:
```bash
php artisan bling:sync-orders --limit=100
```

## 📝 Estrutura do Banco de Dados

### Tabela `orders`

Campos relacionados ao Bling:

```sql
bling_order_number  VARCHAR(255)  -- Número do pedido no Bling
bling_synced_at     TIMESTAMP     -- Última sincronização com Bling
last_bling_sync     TIMESTAMP     -- Última verificação de status
status              VARCHAR(50)   -- Status interno
```

## 🔍 Logs e Debugging

Todos os eventos são logados em `storage/logs/laravel.log`:

```php
// Criação de pedido
[info] Criando pedido no Bling
[info] Pedido criado no Bling com sucesso

// Sincronização de status
[info] Sincronizando status de pedidos com Bling
[info] Status do pedido atualizado

// Webhook
[info] Bling Webhook Received
[info] Order status updated from Bling webhook

// Status Service
[info] Módulo de Vendas encontrado
[info] Situações do Bling carregadas com sucesso
```

## ⚙️ Configuração

### Variáveis de Ambiente

```env
# Bling API v3
BLING_CLIENT_ID=your_client_id
BLING_CLIENT_SECRET=your_client_secret
BLING_BASE_URL=https://api.bling.com.br/Api/v3

# Webhook
BLING_WEBHOOK_URL=https://yourdomain.com/webhook
```

### Cache

- **Chave:** `bling_sales_module_id`
- **TTL:** 24 horas
- **Chave:** `bling_status_list`
- **TTL:** 24 horas

Limpar cache:
```bash
php artisan cache:forget bling_sales_module_id
php artisan cache:forget bling_status_list
```

Ou via comando:
```bash
php artisan bling:fetch-statuses --clear-cache
```

## 🔐 Segurança

### Validação de Webhooks

O `WebhookController` valida webhooks do Bling:

```php
protected function validateWebhook(Request $request): bool
{
    $signature = $request->header('X-Bling-Signature');
    // Implementar HMAC-SHA256 validation
}
```

**TODO:** Implementar validação completa de assinatura HMAC.

## 📚 Referências da API Bling v3

- **Documentação:** https://developer.bling.com.br/
- **Módulos:** `GET /situacoes/modulos`
- **Situações:** `GET /situacoes?idModulo={ID}`
- **Pedidos:** `GET /pedidos/vendas/{id}`
- **Webhooks:** https://developer.bling.com.br/webhooks

## 🎯 Melhorias Futuras

- [ ] Criar pedido no Bling somente após pagamento PIX aprovado
- [ ] Implementar validação HMAC de webhooks
- [ ] Adicionar fila (queue) para sincronização de pedidos
- [ ] Dashboard de monitoramento de sincronização
- [ ] Retry automático para falhas de sincronização
- [ ] Notificações de status para clientes via email/SMS

## 👨‍💻 Uso no Código

### Exemplo: Criar Pedido

```php
use App\Services\Bling\BlingOrderService;

$blingOrderService = app(BlingOrderService::class);
$result = $blingOrderService->createOrder($order);

if ($result['success']) {
    $order->update([
        'bling_order_number' => $result['bling_order_number'],
        'bling_synced_at' => now()
    ]);
}
```

### Exemplo: Sincronizar Status

```php
use App\Services\Bling\BlingOrderService;

$blingOrderService = app(BlingOrderService::class);
$success = $blingOrderService->syncOrderStatus($order);
```

### Exemplo: Usar Enum

```php
use App\Enums\OrderStatus;

// Verificar status
if ($order->status === OrderStatus::PENDING->value) {
    // Pedido pendente
}

// Obter label
$label = OrderStatus::PENDING->label(); // "Pendente"

// Obter cor
$color = OrderStatus::SHIPPED->color(); // "indigo"

// Verificar se é final
$isFinal = OrderStatus::DELIVERED->isFinal(); // true
```

## 📞 Suporte

Para dúvidas sobre a implementação:
- Consultar logs: `storage/logs/laravel.log`
- Executar comandos com `--help`
- Verificar testes unitários (quando implementados)
