# 💳 Simulador de Pagamentos - Mercado Pago PIX

Este documento explica como testar pagamentos PIX em ambiente de desenvolvimento.

## 🎯 Opções Disponíveis

### Opção 1: Simulador Integrado (RECOMENDADO)

**Endpoints disponíveis apenas em desenvolvimento:**

#### 1️⃣ Simular Pagamento PIX Aprovado
```bash
POST http://localhost:8000/api/dev/simulate-pix-payment
Content-Type: application/json

{
  "order_id": 123
}
```

**Resposta:**
```json
{
  "success": true,
  "message": "🧪 Webhook simulado enviado",
  "order_id": 123,
  "order_status": "processing",
  "payment_status": "approved"
}
```

#### 2️⃣ Simular Diferentes Status de Pagamento
```bash
POST http://localhost:8000/api/dev/simulate-payment-status
Content-Type: application/json

{
  "order_id": 123,
  "status": "approved|rejected|pending|in_process|cancelled"
}
```

#### 3️⃣ Listar Pedidos PIX Pendentes
```bash
GET http://localhost:8000/api/dev/pending-pix-orders
```

**Resposta:**
```json
{
  "success": true,
  "total": 3,
  "orders": [
    {
      "id": 123,
      "order_number": "ORD-2024-001",
      "total": "150.00",
      "payment_status": "pending",
      "pix_qr_code": "Disponível",
      "simulate_url": "http://localhost:8000/api/dev/simulate-pix-payment?order_id=123"
    }
  ]
}
```

---

### Opção 2: Webhook Proxy (Para testes com Mercado Pago Real)

Quando você precisa testar com webhooks reais do Mercado Pago:

#### Passo 1: Configurar o Proxy

1. Abra o arquivo: `webhook-proxy.php`
2. Configure seu IP público ou DDNS:
   ```php
   $FORWARD_TO = 'http://SEU_IP_PUBLICO:8000/api/webhooks/mercadopago';
   ```
3. Configure uma senha secreta:
   ```php
   $SECRET_KEY = 'sua_senha_secreta_aqui';
   ```

#### Passo 2: Hospedar no Domínio Real

Upload do arquivo `webhook-proxy.php` para:
```
https://rodust.com.br/webhook-proxy.php
```

#### Passo 3: Configurar no Mercado Pago

Acesse: https://www.mercadopago.com.br/developers/panel/webhooks

Configure:
- **URL do Webhook:** `https://rodust.com.br/webhook-proxy.php`
- **Eventos:** `payment`

#### Passo 4: Liberar Porta no Roteador

Redirecionar porta **8000** externa para seu PC local (ou usar DDNS + port forwarding)

#### Passo 5: Testar

O proxy irá:
1. ✅ Receber webhook do Mercado Pago
2. ✅ Encaminhar para seu localhost
3. ✅ Registrar em `webhook-proxy.log`
4. ✅ Retornar resposta ao Mercado Pago

---

## 🧪 Fluxo de Teste Completo

### 1. Criar um Pedido PIX

```bash
# Via WordPress checkout ou diretamente pela API
POST http://localhost:8000/api/orders
{
  "payment_method": "pix",
  "items": [...],
  "total": 150.00
}
```

### 2. Verificar QR Code Gerado

```bash
GET http://localhost:8000/api/orders/123
```

Resposta terá:
```json
{
  "pix_qr_code": "00020126...",
  "pix_qr_code_base64": "iVBORw0KGgo...",
  "payment_status": "pending"
}
```

### 3. Simular Pagamento

**Opção A: Via Simulador**
```bash
POST http://localhost:8000/api/dev/simulate-pix-payment
{ "order_id": 123 }
```

**Opção B: Via Mercado Pago Sandbox**
- Usar teste account do MP
- Pagar o PIX de teste
- Webhook será chamado automaticamente

### 4. Verificar Status Atualizado

```bash
GET http://localhost:8000/api/orders/123
```

Agora deve mostrar:
```json
{
  "payment_status": "approved",
  "order_status": "processing"
}
```

---

## 🔒 Segurança

### Em Desenvolvimento:
- ✅ Simulador habilitado
- ✅ Logs detalhados
- ✅ Sem autenticação no simulador

### Em Produção:
- ❌ Simulador **automaticamente desabilitado**
- ❌ Webhook proxy deve ser **removido** ou protegido
- ✅ Usar apenas webhooks reais do Mercado Pago
- ✅ Validar assinatura HMAC dos webhooks

---

## 📊 Logs & Debug

### Logs do Simulador:
```bash
docker exec docker-laravel.test-1 tail -f storage/logs/laravel.log | grep "SIMULADOR"
```

### Logs do Webhook:
```bash
docker exec docker-laravel.test-1 tail -f storage/logs/laravel.log | grep "MercadoPago"
```

### Logs do Proxy (se usando):
```bash
# No servidor rodust.com.br
tail -f webhook-proxy.log
```

---

## ⚙️ Configuração do .env

```env
# Mercado Pago - Sandbox (Desenvolvimento)
MERCADOPAGO_PUBLIC_KEY_SANDBOX=APP_USR-xxxxxx
MERCADOPAGO_ACCESS_TOKEN_SANDBOX=APP_USR-xxxxxx

# Mercado Pago - Produção
MERCADOPAGO_PUBLIC_KEY_PROD=APP_USR-xxxxxx
MERCADOPAGO_ACCESS_TOKEN_PROD=APP_USR-xxxxxx

# Webhook (usar proxy em dev, URL real em prod)
MERCADOPAGO_WEBHOOK_URL=https://rodust.com.br/webhook-proxy.php
```

---

## 🎬 Exemplo com cURL

```bash
# 1. Listar pedidos pendentes
curl http://localhost:8000/api/dev/pending-pix-orders

# 2. Simular pagamento do pedido 123
curl -X POST http://localhost:8000/api/dev/simulate-pix-payment \
  -H "Content-Type: application/json" \
  -d '{"order_id": 123}'

# 3. Simular rejeição de pagamento
curl -X POST http://localhost:8000/api/dev/simulate-payment-status \
  -H "Content-Type: application/json" \
  -d '{"order_id": 123, "status": "rejected"}'
```

---

## 📝 Notas Importantes

1. **Simulador só funciona em desenvolvimento** (`APP_ENV=local`)
2. **Webhook proxy é opcional** - use apenas se precisar testar fluxo completo
3. **Em produção**, remover `webhook-proxy.php` do servidor
4. **Status disponíveis**: pending, approved, rejected, in_process, cancelled
5. **Logs são salvos** para debug em `storage/logs/laravel.log`

---

## 🚀 Migração para Produção

Quando for para produção:

1. ❌ Remover `webhook-proxy.php` de rodust.com.br
2. ✅ Configurar webhook direto no Mercado Pago: `https://rodust.com.br/api/webhooks/mercadopago`
3. ✅ Trocar tokens sandbox por produção no `.env`
4. ✅ Configurar `APP_ENV=production`

**Nenhuma alteração necessária no código Laravel!** O simulador será automaticamente desabilitado.
