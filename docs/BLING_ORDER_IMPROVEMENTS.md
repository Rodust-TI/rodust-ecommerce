# Melhorias no Envio de Pedidos ao Bling

## 📋 Resumo das Alterações

### ✅ 1. Taxas do Mercado Pago (CONCLUÍDO)

**Problema:** O sistema não estava capturando as taxas cobradas pelo Mercado Pago.

**Solução:**
- ✅ Adicionada captura de taxas na API do Mercado Pago
- ✅ Criados campos no banco de dados:
  - `payment_fee` - Taxa cobrada pelo gateway
  - `net_amount` - Valor líquido recebido (total - taxa)
  - `payment_details` - Detalhes completos (JSON)
  - `installments` - Número de parcelas
- ✅ Webhook do Mercado Pago atualizado para salvar taxas
- ✅ Taxas incluídas nas observações do pedido no Bling

### ✅ 2. Status "Em Andamento" no Bling

**Problema:** Pedidos estavam indo para o Bling com status "Em aberto" mesmo após pagamento.

**Solução:**
- ✅ Criada configuração dinâmica de status no `config/services.php`
- ✅ Método `denormalizeOrder` atualizado para verificar se pedido foi pago
- ✅ Se `paid_at` ou `status === 'processing'`, envia com status "Em andamento"
- ✅ Caso contrário, envia com status "Em aberto"

### ✅ 3. Parcelas e Formas de Pagamento

**Problema:** Parcelas não estavam sendo enviadas corretamente. Forma de pagamento era fixa.

**Solução:**
- ✅ Sistema de parcelas dinâmico baseado no campo `installments`
- ✅ Criado mapeamento de formas de pagamento por método:
  - PIX → `BLING_PAYMENT_METHOD_PIX`
  - Cartão de Crédito → `BLING_PAYMENT_METHOD_CREDIT_CARD`
  - Cartão de Débito → `BLING_PAYMENT_METHOD_DEBIT_CARD`
  - Boleto → `BLING_PAYMENT_METHOD_BOLETO`
- ✅ Cada parcela é criada com vencimento a cada 30 dias

### ✅ 4. Comandos para Configuração

Criados 2 comandos Artisan para facilitar a configuração:

**a) `php artisan bling:list-payment-methods`**
- Lista todas as formas de pagamento cadastradas no Bling
- Mostra sugestões de mapeamento automático
- Gera código pronto para copiar

**b) `php artisan bling:list-order-statuses`**
- Lista todas as situações (status) de pedidos do Bling
- Identifica automaticamente status como "Em andamento", "Enviado", etc.
- Gera variáveis de ambiente prontas

## 🔧 Configuração Necessária

### Passo 1: Reconectar o Bling

1. Acesse: http://localhost:8000/bling/dashboard
2. Clique em "Conectar ao Bling" ou "Reconectar"
3. Autorize o aplicativo no painel do Bling

### Passo 2: Buscar Formas de Pagamento

```bash
docker exec ecommerce-laravel.test-1 php artisan bling:list-payment-methods
```

Este comando irá:
- Listar todas as formas de pagamento do Bling
- Tentar encontrar automaticamente as que você cadastrou (MercadoPago-PIX, cartaocredito, etc)
- Gerar sugestões de configuração

**Adicione ao `.env`:**
```env
BLING_PAYMENT_METHOD_PIX=123456
BLING_PAYMENT_METHOD_CREDIT_CARD=234567
BLING_PAYMENT_METHOD_DEBIT_CARD=345678
BLING_PAYMENT_METHOD_BOLETO=456789
BLING_PAYMENT_METHOD_DEFAULT=123456
```

### Passo 3: Buscar Status de Pedidos

```bash
docker exec ecommerce-laravel.test-1 php artisan bling:list-order-statuses
```

Este comando irá:
- Listar todas as situações de pedidos de venda
- Identificar "Em aberto", "Em andamento", "Enviado", etc.
- Gerar variáveis de ambiente

**Adicione ao `.env`:**
```env
BLING_ORDER_STATUS_OPEN=987654
BLING_ORDER_STATUS_PROCESSING=876543
BLING_ORDER_STATUS_SHIPPED=765432
BLING_ORDER_STATUS_COMPLETED=654321
BLING_ORDER_STATUS_CANCELLED=543210
```

### Passo 4: Limpar Cache

```bash
docker exec ecommerce-laravel.test-1 php artisan config:clear
docker exec ecommerce-laravel.test-1 php artisan cache:clear
```

## 📊 Como Funciona Agora

### Fluxo de Pedido com PIX:

```
1. Cliente cria pedido → Status: "pending"
   ↓
2. Cliente paga PIX → Webhook Mercado Pago
   ↓
3. Sistema captura:
   - Valor da transação
   - Taxa do Mercado Pago
   - Valor líquido
   - Número de parcelas (1 para PIX)
   ↓
4. Atualiza pedido:
   - status → "processing"
   - paid_at → timestamp atual
   - payment_fee → taxa
   - net_amount → valor líquido
   ↓
5. Envia para Bling:
   - Situação: "Em andamento" (BLING_ORDER_STATUS_PROCESSING)
   - Forma de pagamento: "MercadoPago-PIX" (BLING_PAYMENT_METHOD_PIX)
   - 1 parcela com valor total
   - Observações com taxa e valor líquido
   ↓
6. Envia email de confirmação ao cliente
```

### Fluxo de Pedido com Cartão de Crédito:

```
1. Cliente escolhe cartão → Processa pagamento
   ↓
2. Se aprovado imediatamente:
   - Status → "processing"
   - Envia para Bling na hora
   ↓
3. Webhook confirma:
   - Captura taxas (ex: 3.99% + R$ 0,40)
   - Captura parcelas (ex: 3x de R$ 100)
   - Atualiza dados do pedido
   ↓
4. No Bling:
   - Situação: "Em andamento"
   - Forma de pagamento: "cartaocredito"
   - 3 parcelas de R$ 100 (vencimento 30/60/90 dias)
   - Observações: "Taxa de pagamento: R$ 12,40 | Valor líquido: R$ 287,60"
```

## 🧪 Testando

### Testar Taxa do Mercado Pago (Simulado):

```bash
# Resetar pedido
docker exec ecommerce-laravel.test-1 php artisan tinker --execute="App\Models\Order::find(4)->update(['status' => 'pending', 'payment_status' => 'pending', 'paid_at' => null, 'payment_fee' => null]);"

# Simular pagamento aprovado
$body = @{ order_id = 4; status = 'approved' } | ConvertTo-Json
Invoke-WebRequest -Uri "http://localhost:8000/api/dev/simulate-payment-status" `
  -Method POST -Body $body -ContentType "application/json" -UseBasicParsing
```

### Verificar Pedido:

```bash
docker exec ecommerce-laravel.test-1 php artisan tinker --execute="print_r(App\Models\Order::find(4)->only(['status', 'payment_fee', 'net_amount', 'installments', 'payment_details']));"
```

## 📝 Notas Importantes

1. **Taxas Reais do Mercado Pago:**
   - Em produção, as taxas virão automaticamente da API
   - Em desenvolvimento com simulador, taxas são simuladas (R$ 0 para PIX, ~4% para cartão)

2. **Parcelas:**
   - PIX: sempre 1 parcela
   - Cartão: 1 a 12 parcelas conforme escolha do cliente
   - Cada parcela tem vencimento espaçado de 30 dias

3. **Status no Laravel:**
   - Pedidos ficam "pending" até confirmação de pagamento
   - Após pagamento, mudam para "processing"
   - Apenas pedidos "processing" vão para o Bling

4. **Sincronização:**
   - Pedidos com cartão: vão imediatamente (pagamento já processado)
   - Pedidos com PIX/Boleto: vão após webhook confirmar pagamento
   - Job `SyncOrderToBling` agora passa todos os dados necessários

## 🐛 Troubleshooting

**Erro: "Failed to refresh access token"**
→ Reconecte o Bling pelo dashboard

**Pedidos não aparecem no Bling**
→ Verifique os logs: `docker exec ecommerce-laravel.test-1 tail -n 50 storage/logs/laravel.log`

**Status errado no Bling**
→ Verifique se as variáveis BLING_ORDER_STATUS_* estão corretas

**Forma de pagamento não encontrada**
→ Execute `php artisan bling:list-payment-methods` e configure os IDs corretos

**Taxa não aparece no Bling**
→ As taxas ficam no campo "observações" do pedido, não em campo separado
