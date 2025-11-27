# Arquivos Proxy para Melhor Envio

Estes arquivos fazem a ponte entre o Melhor Envio (que exige domínio .com.br) e seu ambiente local de desenvolvimento.

## 📁 Arquivos

1. **oauth-callback.php** - Recebe callback OAuth do Melhor Envio e redireciona para localhost
2. **webhook.php** - Recebe webhooks do Melhor Envio e encaminha via cURL para localhost

## 🚀 Como usar

### 1. Fazer upload no servidor rodust.com.br

Crie uma pasta `melhor-envio` na raiz do site:

```
/public_html/melhor-envio/
├── oauth-callback.php
└── webhook.php
```

As URLs finais serão:
- https://rodust.com.br/melhor-envio/oauth-callback.php
- https://rodust.com.br/melhor-envio/webhook.php

### 2. Configurar no painel do Melhor Envio

Acesse: https://sandbox.melhorenvio.com.br/painel/gerenciar/tokens

Configure seu aplicativo (Client ID: 15782):
- **URL de Redirecionamento**: `https://rodust.com.br/melhor-envio/oauth-callback.php`
- **URL de Webhook**: `https://rodust.com.br/melhor-envio/webhook.php`

### 3. Permitir seu IP no servidor (se necessário)

Se o servidor bloquear requisições localhost, você pode:

**Opção A**: Usar IP público temporário (ngrok)
**Opção B**: Configurar firewall do servidor para permitir requisições internas

### 4. Logs

Os arquivos criam logs automáticos:
- `oauth-callback.log` - Log de callbacks OAuth
- `webhook.log` - Log de webhooks recebidos

Use para debug caso algo não funcione.

## 🔧 Alternativa: ngrok (Recomendado para desenvolvimento)

É mais simples usar **ngrok** que cria um túnel HTTPS público:

```bash
# Baixar: https://ngrok.com/download
ngrok http 8000
```

Você recebe uma URL tipo `https://abc123.ngrok-free.app` e configura no Melhor Envio:
- OAuth: `https://abc123.ngrok-free.app/api/melhor-envio/oauth/callback`
- Webhook: `https://abc123.ngrok-free.app/api/melhor-envio/webhook`

## ⚠️ Importante

- Os arquivos PHP precisam fazer requisições para `localhost:8000`
- Se seu localhost usar IP/porta diferente, edite a constante `LOCALHOST_URL` nos arquivos
- Em produção, não precisa dos proxies! Use as URLs diretas do Laravel
