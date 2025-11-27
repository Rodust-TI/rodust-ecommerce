# Melhor Envio - Configuração e Credenciais

## 📊 Status Atual

✅ **FUNCIONANDO COM BEARER TOKEN**

O sistema está configurado e testado com sucesso usando o método **Bearer Token** (mais simples).

### Configuração Atual no Banco:
```
Client ID: 15782 (produção - NÃO USADO)
Client Secret: *****EQuf (produção - NÃO USADO)
Bearer Token: ✅ Configurado (1700 chars) - ESTE É USADO!
Modo: Sandbox (Testes)
CEP Origem: 13400-710
```

**💡 Importante:** Como estamos usando Bearer Token, os campos Client ID e Secret **não são utilizados**. Eles só seriam necessários para OAuth2.

---

## 🔑 Suas Credenciais

### Sandbox (Testes)
- **Client ID:** `7552`
- **Client Secret:** `pEe4w3t4uWXlgwT9klHtVD8lnammzb4x123XU8bS`
- **Bearer Token:** (salvo no banco - expira 26/11/2026) abaixo
eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiI5NTYiLCJqdGkiOiJkNzBjODc3OTM5OGE0NTQ2NzI4NWNlMzFjZTVlM2ZiMjU2ZGFiMGM0NTUzZWFhYWZkMDg3NTVjMmMzNDkxMmEwYjRiNDNmZDJkZDVlMzYzZiIsImlhdCI6MTc2NDI1MjgwNC44MDIxMTIsIm5iZiI6MTc2NDI1MjgwNC44MDIxMTUsImV4cCI6MTc5NTc4ODgwNC43ODg4OTIsInN1YiI6IjljNWY5MGM5LTU4NTMtNDM2MS05NTBkLTAwYzlhNDExNWJhZiIsInNjb3BlcyI6WyJjYXJ0LXJlYWQiLCJjYXJ0LXdyaXRlIiwiY29tcGFuaWVzLXJlYWQiLCJjb21wYW5pZXMtd3JpdGUiLCJjb3Vwb25zLXJlYWQiLCJjb3Vwb25zLXdyaXRlIiwibm90aWZpY2F0aW9ucy1yZWFkIiwib3JkZXJzLXJlYWQiLCJwcm9kdWN0cy1yZWFkIiwicHJvZHVjdHMtZGVzdHJveSIsInByb2R1Y3RzLXdyaXRlIiwicHVyY2hhc2VzLXJlYWQiLCJzaGlwcGluZy1jYWxjdWxhdGUiLCJzaGlwcGluZy1jYW5jZWwiLCJzaGlwcGluZy1jaGVja291dCIsInNoaXBwaW5nLWNvbXBhbmllcyIsInNoaXBwaW5nLWdlbmVyYXRlIiwic2hpcHBpbmctcHJldmlldyIsInNoaXBwaW5nLXByaW50Iiwic2hpcHBpbmctc2hhcmUiLCJzaGlwcGluZy10cmFja2luZyIsImVjb21tZXJjZS1zaGlwcGluZyIsInRyYW5zYWN0aW9ucy1yZWFkIiwidXNlcnMtcmVhZCIsInVzZXJzLXdyaXRlIiwid2ViaG9va3MtcmVhZCIsIndlYmhvb2tzLXdyaXRlIiwid2ViaG9va3MtZGVsZXRlIiwidGRlYWxlci13ZWJob29rIl19.NwY_wTw0iBUF766b6ZojTvqOfQbuS6fdNtAMDe5DUPZ3FiKsjVKmKz4Acn5tFtRezAmZ9K7fqo5vocccv3FPnlkRtlULzj87xJyiVGIqMxdD8wcWQV3kDj0vk4bgL-EEvTck0-B3SCFS5zoK4sB3bK-pxrIH6ZT9UIFqi9KcC9IWunbYXJOJJ7AgUrTLoRPGLn-PiIkz_QteBGLuEz9j-tuefsKD-AlyRT_-phjtUI59aay0TB_hm56jHtMyHx2GJ4bccshZQWAzq6lgm23iat92dJaSJuQCPZZLEswFQAX3Sae9PbV4WgobIeVe5x4PVJFd4hhkVhA1XwkxoExzag_N3z4RCNR7jiYzIQLMJLmDUVdUIp5ILM0Qq_64PGuYJTQrh_L_Re7B9U_wfuP6is_w8i9niBxM4tbEs2BUhd0MRTMXK_0gyZSsMe_HaiJTLF9kPggtn_zSpuvCuJOweBmy_VdyRA7uK07fYxziVa6bemdp-oh7IJNlccTdAeguD8zBdyNpjrp7yTrdlTbyakizJBJm1JfIJLklUNksN9IM9RfEV1nCOGXJfjyCOucTP40c95hBOs0IdMhFGjhHF5uuW83LEiwt1q4BZVv16Y3Iqd2oI_eg9Du1KlJV4zJ3FBSlCf9t_LBKGlE2pNpAf9eVqG-UOufYMMXIjeT3vy4
- **URL:** https://sandbox.melhorenvio.com.br

### Produção
- **Client ID:** `15782`
- **Client Secret:** `BXFwSxZoabMZJcVynlk37HXYgpC8C9FzgLBsEQuf`
- **Bearer Token:** (você precisa gerar no painel)
- **URL:** https://melhorenvio.com.br

---

## 🏗️ Arquitetura: Onde ficam as credenciais?

### `.env` (Credenciais FIXAS da aplicação)
```env
# Melhor Envio - Configurações gerais
MELHOR_ENVIO_SANDBOX=true
MELHOR_ENVIO_ORIGIN_CEP=13400710

# OAuth2 - Sandbox
MELHOR_ENVIO_CLIENT_ID_SANDBOX=7552
MELHOR_ENVIO_CLIENT_SECRET_SANDBOX=pEe4w3t4uWXlgwT9klHtVD8lnammzb4x123XU8bS

# OAuth2 - Produção  
MELHOR_ENVIO_CLIENT_ID_PROD=15782
MELHOR_ENVIO_CLIENT_SECRET_PROD=BXFwSxZoabMZJcVynlk37HXYgpC8C9FzgLBsEQuf
```

**Por quê?** Client ID e Secret são fixos, vêm do painel do Melhor Envio e não mudam.

### Banco de Dados (Tokens DINÂMICOS)
Tabela: `melhor_envio_settings`

```sql
- bearer_token (VARCHAR) - Token de acesso direto - RENOVADO ANUALMENTE
- access_token (TEXT) - Token OAuth2 - RENOVADO AUTOMATICAMENTE A CADA 30 DIAS
- refresh_token (TEXT) - Para renovar OAuth2
- expires_at (TIMESTAMP) - Expiração do access_token
```

**Por quê?** Tokens expiram e são renovados, então ficam no banco para serem atualizados dinamicamente.

---

## 🔐 Métodos de Autenticação

### 1️⃣ Bearer Token (RECOMENDADO) ✅

**Quando usar:**
- E-commerce único (1 loja)
- Você é o dono da conta Melhor Envio
- Quer simplicidade

**Como configurar:**
```bash
docker compose exec laravel.test php artisan melhorenvio:setup
# Escolher: "Bearer Token (Recomendado)"
# Colar o token do painel
```

**Como funciona:**
1. Você copia o token do painel Melhor Envio
2. Salva no comando `melhorenvio:setup`
3. Fica no banco na coluna `bearer_token`
4. Sistema usa direto nas requisições: `Authorization: Bearer TOKEN`
5. Válido por 1 ano

**Vantagens:**
- ✅ Simples de configurar
- ✅ Não precisa OAuth
- ✅ Não precisa ngrok/URLs públicas
- ✅ Não expira frequentemente (1 ano)

---

### 2️⃣ OAuth2 (Client ID + Secret)

**Quando usar:**
- Múltiplas lojas (multitenancy)
- Cada cliente precisa autorizar sua conta Melhor Envio
- Aplicativo que outros usuários vão instalar

**Como configurar:**
```bash
docker compose exec laravel.test php artisan melhorenvio:setup
# Escolher: "OAuth2 (Client ID + Secret)"
# Informar Client ID e Secret
# Depois acessar URL de autorização
```

**Como funciona:**
1. Você configura Client ID e Secret (do .env ou comando)
2. Gera URL de autorização: `php artisan melhorenvio:auth-url`
3. Usuário acessa URL e autoriza
4. Sistema recebe `code` no callback
5. Troca `code` por `access_token` e `refresh_token`
6. Salva tokens no banco
7. A cada 30 dias, renova automaticamente

**Vantagens:**
- ✅ Ideal para SaaS/multitenancy
- ✅ Cada usuário autoriza sua conta
- ✅ Renovação automática do token

**Desvantagens:**
- ❌ Mais complexo
- ❌ Precisa URLs públicas (ngrok em dev)
- ❌ Requer configuração de callback no painel

---

## 📁 Estrutura de Arquivos

```
ecommerce/
├── .env                                # Client ID/Secret (fixos)
├── config/services.php                 # Configurações Melhor Envio
├── database/migrations/
│   └── *_create_melhor_envio_settings  # Tabela para tokens
├── app/
│   ├── Models/MelhorEnvioSetting.php   # Model
│   ├── Services/MelhorEnvioService.php # Lógica da API
│   └── Console/Commands/
│       ├── SetupMelhorEnvio.php        # Configurar credenciais
│       ├── ShowMelhorEnvioSettings.php # Ver configurações
│       └── MelhorEnvioGetAuthUrl.php   # Gerar URL OAuth
└── routes/api.php                      # Endpoints (/api/shipping/calculate)
```

---

## 🧪 Comandos Úteis

```bash
# Ver configurações atuais
docker compose exec laravel.test php artisan melhorenvio:show

# Configurar (primeira vez ou reconfigurar)
docker compose exec laravel.test php artisan melhorenvio:setup

# Gerar URL de autorização OAuth (se usar OAuth2)
docker compose exec laravel.test php artisan melhorenvio:auth-url --ngrok-url=https://xxx.ngrok-free.dev

# Testar cálculo de frete
$body = @{postal_code='01310100'; products=@(@{quantity=1; weight=0.5; height=10; width=15; length=20})} | ConvertTo-Json
Invoke-WebRequest -Uri http://localhost:8000/api/shipping/calculate -Method POST -Body $body -ContentType 'application/json'
```

---

## ✅ Recomendação Final

**Para o seu projeto:**

1. **Desenvolvimento (agora):** Continue usando Bearer Token (Sandbox) ✅
2. **Produção (depois):** Gere um novo Bearer Token de produção no painel

**Não precisa mudar nada!** Está funcionando perfeitamente com Bearer Token.

---

## 🔄 Migração Sandbox → Produção

Quando for pra produção:

```bash
# 1. Gerar Bearer Token de PRODUÇÃO no painel Melhor Envio
# 2. Executar:
docker compose exec laravel.test php artisan melhorenvio:setup \
  --bearer-token=NOVO_TOKEN_DE_PRODUCAO \
  --cep=13400710
  # (SEM --sandbox)

# 3. Pronto! ✅
```

---

## 📞 URLs de Webhook (se usar OAuth2 futuramente)

**Desenvolvimento (com ngrok):**
```
https://xxx.ngrok-free.dev/api/melhor-envio/oauth/callback
https://xxx.ngrok-free.dev/api/melhor-envio/webhook
```

**Produção:**
```
https://api.rodust.com.br/api/melhor-envio/oauth/callback
https://api.rodust.com.br/api/melhor-envio/webhook
```

---

**💡 Resumindo:** Você está usando a forma mais simples (Bearer Token) e está funcionando perfeitamente! Não precisa se preocupar com Client ID/Secret por enquanto. 🚀
