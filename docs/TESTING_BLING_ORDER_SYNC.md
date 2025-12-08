# Teste Completo: Envio de Pedido ao Bling

## 🎯 Objetivo

Testar o fluxo completo de envio de pedido ao Bling com:
- Status "Em andamento"
- Forma de pagamento correta (PIX ou Cartão)
- Parcelas configuradas
- Taxas do Mercado Pago nas observações

## 📋 Pré-requisitos

1. ✅ Bling conectado
2. ✅ Configurações no `.env`:
   ```env
   BLING_PAYMENT_METHOD_PIX=8652403
   BLING_PAYMENT_METHOD_CREDIT_CARD=8652763
   BLING_ORDER_STATUS_PROCESSING=1
   ```
3. ✅ Cache limpo: `php artisan config:clear`

## 🧪 Teste 1: Pedido PIX Pago

### Passo 1: Resetar pedido para estado inicial

```bash
docker exec ecommerce-laravel.test-1 php artisan tinker --execute="
\$order = App\Models\Order::find(4);
if (\$order) {
    \$order->update([
        'status' => 'pending',
        'payment_status' => 'pending',
        'payment_method' => 'pix',
        'paid_at' => null,
        'payment_fee' => null,
        'net_amount' => null,
        'installments' => 1,
        'bling_order_number' => null,
        'bling_synced_at' => null
    ]);
    echo 'Pedido #4 resetado para PIX pendente\n';
} else {
    echo 'Pedido #4 não encontrado\n';
}
"
```

### Passo 2: Simular pagamento aprovado

```powershell
$body = @{
    order_id = 4
    status = 'approved'
} | ConvertTo-Json

Invoke-WebRequest -Uri "http://localhost:8000/api/dev/simulate-payment-status" `
  -Method POST `
  -Body $body `
  -ContentType "application/json" `
  -UseBasicParsing
```

### Passo 3: Verificar atualização do pedido

```bash
docker exec ecommerce-laravel.test-1 php artisan tinker --execute="
\$order = App\Models\Order::find(4);
echo json_encode([
    'id' => \$order->id,
    'order_number' => \$order->order_number,
    'status' => \$order->status,
    'payment_status' => \$order->payment_status,
    'payment_method' => \$order->payment_method,
    'payment_fee' => \$order->payment_fee,
    'net_amount' => \$order->net_amount,
    'installments' => \$order->installments,
    'paid_at' => \$order->paid_at?->format('Y-m-d H:i:s'),
    'bling_order_number' => \$order->bling_order_number,
    'bling_synced_at' => \$order->bling_synced_at?->format('Y-m-d H:i:s')
], JSON_PRETTY_PRINT);
"
```

**Esperado:**
```json
{
    "status": "processing",
    "payment_status": "approved",
    "payment_method": "pix",
    "paid_at": "2025-12-02 XX:XX:XX",
    "installments": 1
}
```

### Passo 4: Verificar logs de sincronização

```bash
docker exec ecommerce-laravel.test-1 tail -n 50 storage/logs/laravel.log | grep -E "SyncOrder|Bling|pedido"
```

### Passo 5: Verificar no Bling

1. Acesse: https://www.bling.com.br/pedidos.vendas.php
2. Busque pelo número do pedido
3. Verifique:
   - ✅ Status: "Em andamento"
   - ✅ Forma de pagamento: "MercadoPago-PIX"
   - ✅ 1 parcela
   - ✅ Observações contém taxa (se houver)

---

## 🧪 Teste 2: Pedido Cartão de Crédito (3x)

### Passo 1: Criar pedido simulado com cartão

```bash
docker exec ecommerce-laravel.test-1 php artisan tinker --execute="
\$order = App\Models\Order::find(3);
if (\$order) {
    \$order->update([
        'status' => 'pending',
        'payment_status' => 'pending',
        'payment_method' => 'credit_card',
        'paid_at' => null,
        'payment_fee' => null,
        'net_amount' => null,
        'installments' => 3,
        'bling_order_number' => null,
        'bling_synced_at' => null
    ]);
    echo 'Pedido #3 configurado para cartão 3x\n';
}
"
```

### Passo 2: Simular pagamento aprovado com taxas

```bash
docker exec ecommerce-laravel.test-1 php artisan tinker --execute="
\$order = App\Models\Order::find(3);
\$total = \$order->total;
\$fee = \$total * 0.0399 + 0.40; // Taxa típica MP: 3.99% + R$ 0,40
\$net = \$total - \$fee;

\$order->update([
    'status' => 'processing',
    'payment_status' => 'approved',
    'paid_at' => now(),
    'payment_fee' => round(\$fee, 2),
    'net_amount' => round(\$net, 2),
    'installments' => 3
]);

// Despachar job de sincronização
App\Jobs\SyncOrderToBling::dispatch(\$order);

echo 'Pedido #3 aprovado e enviado ao Bling\n';
echo 'Total: R$ ' . \$total . '\n';
echo 'Taxa: R$ ' . round(\$fee, 2) . '\n';
echo 'Líquido: R$ ' . round(\$net, 2) . '\n';
"
```

### Passo 3: Verificar no Bling

Deve aparecer:
- ✅ Status: "Em andamento"
- ✅ Forma de pagamento: "MercadoPago-CartaoCredito"
- ✅ 3 parcelas (vencimento 30/60/90 dias)
- ✅ Observações: "Taxa de pagamento: R$ X,XX | Valor líquido: R$ Y,YY"

---

## 🧪 Teste 3: Verificar Payload Enviado ao Bling

Para ver exatamente o que está sendo enviado ao Bling:

```bash
docker exec ecommerce-laravel.test-1 php artisan tinker --execute="
\$order = App\Models\Order::with(['customer', 'items.product'])->find(4);

\$orderData = [
    'order_number' => \$order->order_number,
    'status' => \$order->status,
    'paid_at' => \$order->paid_at,
    'customer' => [
        'id' => \$order->customer->bling_id ?? null,
        'name' => \$order->customer->name,
        'email' => \$order->customer->email,
        'phone' => \$order->customer->phone,
    ],
    'items' => \$order->items->map(function (\$item) {
        return [
            'bling_id' => \$item->product->bling_id ?? null,
            'sku' => \$item->product_sku,
            'name' => \$item->product_name,
            'quantity' => \$item->quantity,
            'price' => \$item->unit_price,
        ];
    })->toArray(),
    'shipping' => \$order->shipping ?? 0,
    'discount' => \$order->discount ?? 0,
    'payment_method' => \$order->payment_method,
    'payment_fee' => \$order->payment_fee ?? 0,
    'net_amount' => \$order->net_amount ?? \$order->total,
    'installments' => \$order->installments ?? 1,
];

echo json_encode(\$orderData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
"
```

---

## 📊 Resultados Esperados

### Pedido PIX (1x):

```json
{
  "situacao": { "id": 1 },
  "parcelas": [
    {
      "valor": 17.75,
      "dataVencimento": "2025-01-01",
      "observacoes": "Parcela 1/1 - Pagamento via PIX",
      "formaPagamento": { "id": 8652403 }
    }
  ]
}
```

### Pedido Cartão (3x):

```json
{
  "situacao": { "id": 1 },
  "parcelas": [
    {
      "valor": 5.92,
      "dataVencimento": "2025-01-01",
      "observacoes": "Parcela 1/3 - Pagamento via CREDIT_CARD",
      "formaPagamento": { "id": 8652763 }
    },
    {
      "valor": 5.92,
      "dataVencimento": "2025-01-31",
      "observacoes": "Parcela 2/3 - Pagamento via CREDIT_CARD",
      "formaPagamento": { "id": 8652763 }
    },
    {
      "valor": 5.91,
      "dataVencimento": "2025-03-02",
      "observacoes": "Parcela 3/3 - Pagamento via CREDIT_CARD",
      "formaPagamento": { "id": 8652763 }
    }
  ],
  "observacoes": "Taxa de pagamento: R$ 1,11 | Valor líquido: R$ 16,64"
}
```

---

## 🐛 Troubleshooting

### Erro: "Class App\Models\Product does not exist"

```bash
# Verificar relação items → product
docker exec ecommerce-laravel.test-1 php artisan tinker --execute="
\$item = App\Models\OrderItem::first();
echo get_class(\$item->product);
"
```

### Erro: "Undefined array key 'bling_id'"

O item não tem produto associado. Corrigir:

```bash
docker exec ecommerce-laravel.test-1 php artisan tinker --execute="
\$items = App\Models\OrderItem::whereNull('product_id')->get();
foreach (\$items as \$item) {
    \$product = App\Models\Product::where('sku', \$item->product_sku)->first();
    if (\$product) {
        \$item->update(['product_id' => \$product->id]);
    }
}
echo 'Itens corrigidos: ' . \$items->count();
"
```

### Pedido não aparece no Bling

1. Verificar logs:
   ```bash
   docker exec ecommerce-laravel.test-1 tail -n 100 storage/logs/laravel.log | grep -A 10 "SyncOrderToBling"
   ```

2. Verificar queue worker:
   ```bash
   docker logs docker-laravel.queue-1 --tail 50
   ```

3. Executar job manualmente:
   ```bash
   docker exec ecommerce-laravel.test-1 php artisan tinker --execute="
   \$order = App\Models\Order::find(4);
   App\Jobs\SyncOrderToBling::dispatchSync(\$order);
   "
   ```

---

## ✅ Checklist de Sucesso

- [ ] Pedido muda de `pending` → `processing`
- [ ] `paid_at` preenchido com timestamp
- [ ] `payment_fee` e `net_amount` calculados
- [ ] `bling_order_number` preenchido após sync
- [ ] Pedido aparece no Bling com status "Em andamento"
- [ ] Forma de pagamento correta no Bling
- [ ] Número correto de parcelas
- [ ] Taxa nas observações (se houver)
- [ ] Email enviado ao cliente
- [ ] Logs sem erros
