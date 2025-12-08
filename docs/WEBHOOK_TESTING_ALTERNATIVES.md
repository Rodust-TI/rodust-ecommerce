# Alternativas para Testar Webhook do Mercado Pago Localmente

O Mercado Pago não aceita webhooks para `localhost`, então precisamos de uma solução para expor nossa aplicação local na internet. Aqui estão as melhores alternativas ao ngrok:

## 🎯 Opção 1: Cloudflare Tunnel (RECOMENDADO - GRÁTIS)

**Vantagens:**
- ✅ Totalmente gratuito
- ✅ Não expira
- ✅ URLs estáveis
- ✅ Sem limites de requisições
- ✅ Mantido pela Cloudflare (confiável)

### Instalação:

**Windows:**
```powershell
# Baixar cloudflared
Invoke-WebRequest -Uri "https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe" -OutFile "cloudflared.exe"

# Mover para pasta acessível
Move-Item cloudflared.exe C:\Windows\System32\cloudflared.exe
```

### Uso:

```powershell
# Expor porta 8000 (Laravel)
cloudflared tunnel --url http://localhost:8000
```

O comando retornará uma URL como: `https://xxxxx-xxx-xxx.trycloudflare.com`

### Configurar no Mercado Pago:

1. Copie a URL gerada (ex: `https://xxxxx-xxx-xxx.trycloudflare.com`)
2. No painel do Mercado Pago, configure o webhook:
   ```
   https://xxxxx-xxx-xxx.trycloudflare.com/api/webhooks/mercadopago
   ```

**⚠️ Nota:** A URL muda toda vez que você reinicia o tunnel. Para URL fixa, crie uma conta gratuita na Cloudflare.

---

## 🎯 Opção 2: LocalTunnel (SIMPLES E RÁPIDO)

**Vantagens:**
- ✅ Gratuito
- ✅ Fácil de usar
- ✅ Sem conta necessária
- ⚠️ URLs mudam a cada execução

### Instalação:

```powershell
# Instalar via npm (requer Node.js)
npm install -g localtunnel
```

### Uso:

```powershell
# Expor porta 8000
lt --port 8000
```

Retorna: `https://random-name.loca.lt`

**⚠️ Primeira vez:** LocalTunnel mostra uma tela de confirmação. Clique em "Continue" para prosseguir.

---

## 🎯 Opção 3: Serveo (SEM INSTALAÇÃO)

**Vantagens:**
- ✅ Não precisa instalar nada
- ✅ Usa SSH nativo

### Uso:

```powershell
# Windows (com OpenSSH)
ssh -R 80:localhost:8000 serveo.net
```

Retorna: `https://something.serveo.net`

---

## 🎯 Opção 4: Bore (MODERNO E LEVE)

**Vantagens:**
- ✅ Muito rápido
- ✅ Código aberto
- ✅ Sem dependências

### Instalação:

```powershell
# Baixar versão Windows
Invoke-WebRequest -Uri "https://github.com/ekzhang/bore/releases/latest/download/bore-v0.5.1-x86_64-pc-windows-msvc.zip" -OutFile "bore.zip"
Expand-Archive bore.zip -DestinationPath C:\Windows\System32
```

### Uso:

```powershell
bore local 8000 --to bore.pub
```

---

## 🎯 Opção 5: Webhook Relay (PARA PRODUÇÃO)

Se você já tem o site em produção (`rodust.com.br`), pode usar o próprio servidor em produção para receber webhooks e repassar para seu localhost:

### Criar endpoint relay no servidor:

```php
// Em rodust.com.br/webhook-relay.php
<?php
$webhook = file_get_contents('php://input');
$headers = getallheaders();

// Enviar para localhost via Cloudflare Tunnel ou outro método
$ch = curl_init('https://seu-tunnel.trycloudflare.com/api/webhooks/mercadopago');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $webhook);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Signature: ' . ($headers['X-Signature'] ?? ''),
    'X-Request-Id: ' . ($headers['X-Request-Id'] ?? '')
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

echo "OK";
```

---

## 📋 Passo a Passo Recomendado (Cloudflare Tunnel)

### 1. Instalar Cloudflare Tunnel:

```powershell
# Baixar
Invoke-WebRequest -Uri "https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe" -OutFile "$env:USERPROFILE\Downloads\cloudflared.exe"

# Mover para local acessível
Move-Item "$env:USERPROFILE\Downloads\cloudflared.exe" "C:\Program Files\cloudflared.exe"

# Adicionar ao PATH (opcional)
[Environment]::SetEnvironmentVariable("Path", $env:Path + ";C:\Program Files", [EnvironmentVariableTarget]::Machine)
```

### 2. Iniciar Tunnel:

```powershell
cloudflared tunnel --url http://localhost:8000
```

### 3. Copiar URL gerada:

```
Your quick Tunnel has been created! Visit it at (it may take some time to be reachable):
https://random-name-1234.trycloudflare.com
```

### 4. Testar manualmente:

```powershell
# Testar se funciona
Invoke-WebRequest -Uri "https://random-name-1234.trycloudflare.com/api/webhooks/mercadopago" -Method POST -ContentType "application/json" -Body '{"test":true}'
```

### 5. Configurar no Mercado Pago:

1. Acesse: https://www.mercadopago.com.br/developers/panel/app
2. Selecione sua aplicação
3. Vá em "Webhooks"
4. Configure:
   - **URL:** `https://random-name-1234.trycloudflare.com/api/webhooks/mercadopago`
   - **Eventos:** `payment.created`, `payment.updated`

### 6. Testar pagamento:

Agora quando você fizer um pagamento de teste, o webhook será enviado para sua aplicação local!

---

## 🧪 Testar Webhook Manualmente

Mesmo com tunnel configurado, você pode simular webhooks manualmente:

```powershell
# Simular webhook de pagamento aprovado
$body = @{
    action = "payment.updated"
    api_version = "v1"
    data = @{ id = "1234567890" }
    date_created = (Get-Date -Format "o")
    id = Get-Random -Minimum 1000000 -Maximum 9999999
    live_mode = $false
    type = "payment"
    user_id = "123456"
} | ConvertTo-Json

Invoke-WebRequest -Uri "https://random-name-1234.trycloudflare.com/api/webhooks/mercadopago" `
  -Method POST `
  -Body $body `
  -ContentType "application/json"
```

---

## ⚙️ Configuração Permanente (Cloudflare com Conta)

Para evitar URLs que mudam, crie uma conta gratuita:

### 1. Login Cloudflare:

```powershell
cloudflared tunnel login
```

### 2. Criar Tunnel:

```powershell
cloudflared tunnel create rodust-dev
```

### 3. Configurar DNS (subdomínio próprio):

```powershell
cloudflared tunnel route dns rodust-dev dev.rodust.com.br
```

### 4. Criar arquivo config:

**Criar:** `C:\Users\SeuUsuario\.cloudflared\config.yml`

```yaml
tunnel: rodust-dev
credentials-file: C:\Users\SeuUsuario\.cloudflared\<tunnel-id>.json

ingress:
  - hostname: dev.rodust.com.br
    service: http://localhost:8000
  - service: http_status:404
```

### 5. Rodar tunnel:

```powershell
cloudflared tunnel run rodust-dev
```

Agora você tem uma URL permanente: `https://dev.rodust.com.br`

---

## 🎯 Comparação Rápida

| Ferramenta | Gratuito | Instalação | URL Fixa | Confiabilidade |
|------------|----------|------------|----------|----------------|
| Cloudflare | ✅ Sim | Sim | ✅ Sim* | ⭐⭐⭐⭐⭐ |
| LocalTunnel | ✅ Sim | Sim (npm) | ❌ Não | ⭐⭐⭐ |
| Serveo | ✅ Sim | ❌ Não | ❌ Não | ⭐⭐⭐ |
| Bore | ✅ Sim | Sim | ❌ Não | ⭐⭐⭐⭐ |
| ngrok | ⚠️ Limitado | Sim | ✅ Sim** | ⭐⭐⭐⭐⭐ |

\* Com conta gratuita  
\*\* Apenas plano pago

---

## 🐛 Troubleshooting

**Cloudflare Tunnel não conecta:**
```powershell
# Verificar se há proxy/firewall bloqueando
Test-NetConnection -ComputerName cftunnel.com -Port 443
```

**LocalTunnel pede senha:**
```powershell
# Usar outro servidor
lt --port 8000 --host https://localtunnel.me
```

**Webhook não chega:**
1. Verifique os logs: `docker exec ecommerce-laravel.test-1 tail -f storage/logs/laravel.log`
2. Teste diretamente: `curl -X POST https://seu-tunnel/api/webhooks/mercadopago`
3. Verifique firewall/antivírus

---

## 💡 Recomendação Final

**Para desenvolvimento:** Use **Cloudflare Tunnel** (gratuito, confiável, fácil)

**Para testes rápidos:** Use **Serveo** (não precisa instalar nada)

**Para produção:** Configure webhooks direto no servidor `rodust.com.br`
