# 🚀 Guia Rápido: Testar Mercado Pago PIX com Webhook Real

## O Problema

Mercado Pago não aceita `localhost` ou `127.0.0.1` nas URLs de webhook. Precisamos de uma URL pública que encaminhe para seu ambiente local.

## A Solução: Webhook Proxy

```
Mercado Pago → rodust.com.br/webhook-proxy.php → Seu PC Local (localhost:8000)
```

---

## 📋 Passo a Passo

### 1️⃣ Descobrir Seu IP Público

Abra o PowerShell e execute:

```powershell
# Descobrir seu IP público
Invoke-RestMethod -Uri 'https://api.ipify.org?format=json' | Select-Object -ExpandProperty ip
```

**OU** acesse: https://meuip.com.br

**Anote o IP**: Ex: `200.123.45.67`

---

### 2️⃣ Configurar o Webhook Proxy

Abra o arquivo: `ecommerce/webhook-proxy.php`

**Edite a linha 22:**
```php
$FORWARD_TO = 'http://200.123.45.67:8000/api/webhooks/mercadopago';
//                  ^^^^^^^^^^^^^^^^ SEU IP AQUI
```

**Opcional - Mudar a senha (linha 25):**
```php
$SECRET_KEY = 'sua_senha_super_secreta_aqui';
```

---

### 3️⃣ Liberar Porta 8000 no Roteador

**Acesse o admin do seu roteador:**
- Geralmente: `192.168.0.1` ou `192.168.1.1`
- Usuário/Senha: veja etiqueta do roteador ou manual

**Configurar Port Forwarding:**
```
Porta Externa: 8000
Porta Interna: 8000
IP Interno: <IP do seu PC na rede local> (ex: 192.168.0.105)
Protocolo: TCP
```

**Como descobrir IP do seu PC na rede local:**
```powershell
ipconfig | Select-String "IPv4"
```
Procure por algo como `192.168.0.xxx`

**Testar se funcionou:**
```powershell
# Em outro PC ou no celular (usando 4G, não WiFi):
curl http://SEU_IP_PUBLICO:8000
```
Deve retornar a página do Laravel.

---

### 4️⃣ Fazer Upload do Proxy para rodust.com.br

**Via FTP/cPanel:**

Fazer upload do arquivo `webhook-proxy.php` para:
```
/public_html/webhook-proxy.php
```

**Testar se está acessível:**
Abra no navegador: https://rodust.com.br/webhook-proxy.php

Deve mostrar erro `{"error":"Webhook proxy not configured"}` (normal, pois ainda não configurou).

Após configurar o `$FORWARD_TO`, deve mostrar:
```json
{"error":"Failed to forward to localhost","details":"Connection refused"}
```
Isso é normal se a porta 8000 não estiver liberada ainda.

---

### 5️⃣ Configurar Webhook no Mercado Pago

**Acesse:** https://www.mercadopago.com.br/developers/panel/webhooks

**Criar novo webhook:**
- **URL de produção**: `https://rodust.com.br/webhook-proxy.php`
- **Eventos**: Marcar `payment`
- Salvar

**Copiar o Webhook Secret** que o Mercado Pago gerar (se houver).

---

### 6️⃣ Testar o Fluxo Completo

#### A) Criar um Pedido PIX no WordPress

1. Acesse: http://localhost:8443 (WordPress)
2. Adicione produtos ao carrinho
3. Finalize compra escolhendo **PIX**
4. Copie o código PIX ou tire print do QR Code

#### B) Pagar o PIX (Ambiente de Testes)

**Opção 1: Usar Carteira de Testes do Mercado Pago**
- Acesse: https://www.mercadopago.com.br/developers/pt/docs/checkout-api/testing
- Use o app do Mercado Pago em modo sandbox
- Scaneie o QR Code de teste

**Opção 2: API de Simulação do Mercado Pago**
```bash
# Aprovar pagamento via API (sandbox)
curl -X PUT \
  https://api.mercadopago.com/v1/payments/{payment_id} \
  -H 'Authorization: Bearer SEU_ACCESS_TOKEN_SANDBOX' \
  -H 'Content-Type: application/json' \
  -d '{"status": "approved"}'
```

#### C) Verificar se o Webhook Chegou

**No servidor rodust.com.br**, visualize o log:
```bash
tail -f /public_html/webhook-proxy.log
```

Deve mostrar algo como:
```
[2024-12-01 18:30:15] Recebido: POST ?data.id=12345678
[2024-12-01 18:30:15] Body: {"action":"payment.updated","data":{"id":"12345678"}...
[2024-12-01 18:30:16] Encaminhado com sucesso. Status: 200
```

**No Laravel (localhost):**
```bash
docker exec docker-laravel.test-1 tail -f storage/logs/laravel.log | grep -i mercadopago
```

Deve mostrar:
```
[2024-12-01 18:30:16] Webhook MercadoPago recebido
[2024-12-01 18:30:16] Pagamento aprovado: #12345678
[2024-12-01 18:30:16] Pedido #123 atualizado: payment_status = approved
```

---

## 🔍 Troubleshooting

### ❌ Erro: "Connection refused"

**Causa:** Porta 8000 não está acessível do exterior.

**Solução:**
1. Verificar se Laravel está rodando: `docker ps | grep laravel`
2. Verificar se porta está liberada no roteador
3. Verificar firewall do Windows:
   ```powershell
   netsh advfirewall firewall add rule name="Laravel Port 8000" dir=in action=allow protocol=TCP localport=8000
   ```

### ❌ Erro: "404 Not Found" no webhook

**Causa:** Rota `/api/webhooks/mercadopago` não existe ou está diferente.

**Verificar rota:**
```bash
docker exec docker-laravel.test-1 php artisan route:list --path=webhooks
```

### ❌ Webhook não chega

**Causa:** Mercado Pago não consegue acessar rodust.com.br/webhook-proxy.php

**Testar manualmente:**
```bash
curl -X POST https://rodust.com.br/webhook-proxy.php \
  -H "Content-Type: application/json" \
  -d '{"action":"payment.updated","data":{"id":"test123"}}'
```

### ❌ IP Público muda constantemente

**Solução:** Usar DDNS (IP Dinâmico)

**Serviços gratuitos:**
- No-IP: https://www.noip.com
- DuckDNS: https://www.duckdns.org
- FreeDNS: https://freedns.afraid.org

Configurar router para atualizar DDNS automaticamente.

Trocar no `webhook-proxy.php`:
```php
$FORWARD_TO = 'http://seudominio.ddns.net:8000/api/webhooks/mercadopago';
```

---

## 🎯 Alternativa: Cloudflare Tunnel (Sem Port Forwarding!)

Se não conseguir liberar porta no roteador, use Cloudflare Tunnel:

```bash
# Instalar cloudflared
# https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/downloads/

# Criar tunnel
cloudflared tunnel --url http://localhost:8000
```

Isso gera uma URL pública temporária:
```
https://random-name.trycloudflare.com
```

Usar essa URL no `webhook-proxy.php`:
```php
$FORWARD_TO = 'https://random-name.trycloudflare.com/api/webhooks/mercadopago';
```

**Vantagens:**
- ✅ Não precisa liberar porta no roteador
- ✅ Não precisa IP público fixo
- ✅ Funciona atrás de CGNAT

**Desvantagens:**
- ❌ URL muda a cada reinicialização
- ❌ Precisa reconfigurar webhook-proxy.php sempre

---

## 🚀 Checklist Final

- [ ] IP público descoberto ou DDNS configurado
- [ ] Porta 8000 liberada no roteador
- [ ] Firewall Windows permite porta 8000
- [ ] Laravel acessível via IP público (`http://SEU_IP:8000`)
- [ ] `webhook-proxy.php` editado com IP correto
- [ ] `webhook-proxy.php` hospedado em rodust.com.br
- [ ] Webhook configurado no painel do Mercado Pago
- [ ] Teste de pagamento PIX realizado
- [ ] Webhook recebido e processado com sucesso

---

## 📝 Para Produção

Quando publicar o site em produção:

1. ❌ **DELETAR** `webhook-proxy.php` do servidor
2. ✅ Configurar webhook direto: `https://rodust.com.br/api/webhooks/mercadopago`
3. ✅ Usar tokens de **produção** (não sandbox)
4. ✅ Configurar `APP_ENV=production`

**Vantagem:** Em produção, o webhook chega direto no servidor. Zero configuração adicional! 🎯
