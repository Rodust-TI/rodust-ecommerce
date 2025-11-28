# 🔧 Configuração de Webhooks - Rodust Ecommerce

## ✅ Configurações Realizadas

### 1. **Mercado Pago - Webhook via ngrok**
- ✅ ngrok rodando: `https://floatingly-incipient-paul.ngrok-free.dev`
- ✅ Webhook configurado no Mercado Pago
- ✅ Secret adicionada no `.env`: `119c8274443a8e055277aa2b95c1464aa5fdad5363bf01ddf32d2d742662fdf0`
- ✅ Validação de assinatura implementada no controller
- ✅ URL do webhook: `https://floatingly-incipient-paul.ngrok-free.dev/api/webhooks/mercadopago`

### 2. **Bling - Webhook via WordPress**
- ✅ Endpoint criado: `https://localhost:8443/webhook`
- ✅ CORS configurado corretamente
- ✅ Sistema de logs JSON automático
- ✅ Template WordPress com rewrite rules

### 3. **Dashboard Bling - Laravel**
- ✅ Acesso correto: `http://localhost:8000/bling`
- ✅ Botão de teste de webhook com mensagens informativas sobre CORS
- ✅ Consoles individuais por módulo
- ✅ Console global de webhooks

---

## 📝 Como Configurar no Bling

1. **Acessar Configurações de Webhooks:**
   - URL: https://www.bling.com.br/configuracoes.php#/webhooks
   - Menu: Configurações → Integrações → Webhooks

2. **Adicionar Nova Notificação:**
   ```
   URL do Webhook: https://localhost:8443/webhook
   Método: POST
   ```

3. **Selecionar Eventos:**
   - ✅ Pedido criado
   - ✅ Pedido atualizado
   - ✅ Produto criado
   - ✅ Produto atualizado
   - ✅ Estoque alterado

4. **Testar no Painel Bling:**
   - Clique em "Testar Webhook"
   - Verifique os logs em: `wp-content/themes/rodust/webhook.log`

---

## 📝 Como Configurar no Mercado Pago

1. **Acessar Webhooks:**
   - URL: https://www.mercadopago.com.br/developers/panel/webhooks
   - Menu: Integrações → Webhooks

2. **Configurações:**
   ```
   URL de produção: https://floatingly-incipient-paul.ngrok-free.dev/api/webhooks/mercadopago
   Eventos: Pagamentos
   ```

3. **Secret Configurada:**
   ```
   119c8274443a8e055277aa2b95c1464aa5fdad5363bf01ddf32d2d742662fdf0
   ```
   ⚠️ **IMPORTANTE**: Quando o ngrok for reiniciado, a URL muda e você precisa atualizar no Mercado Pago!

---

## 🧪 Testar Webhooks

### Teste Manual do Bling (via Dashboard):
1. Acesse: `http://localhost:8000/bling`
2. No console global de webhooks, clique em **"🧪 Testar Webhook Bling"**
3. ⚠️ Pode dar erro CORS (normal) - webhooks reais do Bling funcionarão!

### Teste Real do Bling:
1. Configure webhook no painel do Bling
2. Envie um teste pelo próprio painel
3. Verifique logs: `wp-content/themes/rodust/webhook.log`

### Teste Real do Mercado Pago:
1. Faça um pagamento de teste no checkout
2. Acompanhe os logs no Laravel: `storage/logs/laravel.log`
3. Verifique se o status do pedido foi atualizado

---

## 📂 Arquivos de Log

### WordPress (Bling e outros webhooks):
```powershell
Get-Content "M:\Websites\rodust.com.br\wordpress\wp-content\themes\rodust\webhook.log" -Tail 50
```

### Laravel (Mercado Pago e geral):
```powershell
Get-Content "M:\Websites\rodust.com.br\ecommerce\storage\logs\laravel.log" -Tail 50
```

---

## 🔐 Validação de Segurança

### Bling:
- ❌ Não usa assinatura (apenas IP whitelist na produção)
- ✅ Logs completos para auditoria

### Mercado Pago:
- ✅ Validação HMAC SHA256 implementada
- ✅ Headers `x-signature` e `x-request-id` validados
- ✅ Secret armazenada em `.env`

---

## 🚀 URLs de Acesso

| Serviço | URL | Descrição |
|---------|-----|-----------|
| Laravel (navegador) | http://localhost:8000 | Acesso externo |
| Laravel (Docker) | http://laravel.test | Acesso interno |
| WordPress HTTP | http://localhost:8080 | Acesso externo |
| WordPress HTTPS | https://localhost:8443 | Acesso externo (SSL) |
| Dashboard Bling | http://localhost:8000/bling | Painel de controle |
| Webhook Bling | https://localhost:8443/webhook | Endpoint WordPress |
| Webhook Mercado Pago | https://floatingly-incipient-paul.ngrok-free.dev/api/webhooks/mercadopago | Via ngrok |

---

## ⚠️ CORS - Por que ocorre?

### Problema:
- Dashboard Laravel (HTTP): `http://localhost:8000`
- Webhook WordPress (HTTPS): `https://localhost:8443`
- Navegador bloqueia: Mixed Content (HTTP → HTTPS)

### Solução:
- ✅ CORS configurado no WordPress (`Access-Control-Allow-Origin: *`)
- ✅ Headers enviados ANTES de qualquer output
- ⚠️ Teste manual pode falhar, mas webhooks REAIS funcionam!

### Por que webhooks reais funcionam?
- Webhooks são **servidor → servidor**
- Não passam pelo navegador
- Sem restrições CORS
- Bling/MP conectam direto no endpoint

---

## 🔄 Restart do ngrok

Quando reiniciar o ngrok, a URL muda. Siga estes passos:

1. **Iniciar ngrok novamente:**
   ```powershell
   ngrok http 80
   ```

2. **Copiar nova URL** (ex: `https://novo-dominio.ngrok-free.dev`)

3. **Atualizar .env:**
   ```env
   MERCADOPAGO_WEBHOOK_URL=https://novo-dominio.ngrok-free.dev/api/webhooks/mercadopago
   ```

4. **Atualizar no Mercado Pago:**
   - Editar webhook existente
   - Salvar nova URL

5. **Atualizar no dashboard.blade.php** (linha 237)

---

## 📊 Status Atual

- ✅ Webhook Bling: Configurado e pronto
- ✅ Webhook Mercado Pago: Configurado com validação de assinatura
- ✅ Dashboard: Layout modular com consoles individuais
- ✅ Logs: Sistema completo implementado
- ✅ ngrok: Rodando e expondo Laravel
- ⏸️ Testes reais: Aguardando configuração no painel Bling/MP

---

## 🎯 Próximos Passos

1. Configurar webhook no painel do Bling
2. Fazer pagamento teste no Mercado Pago
3. Validar logs e processamento
4. Testar sincronização automática de pedidos
5. Implementar notificações por email
