# Alternativas ao UltraHook para Webhooks

## Problema Atual

O UltraHook tem limitações quando se trata de múltiplos túneis simultâneos. Cada túnel precisa ser iniciado separadamente e pode haver conflitos.

## Soluções Alternativas

### 1. **ngrok** (Recomendado)

**Vantagens:**
- Suporta múltiplos túneis simultâneos
- Interface web para monitoramento
- Gratuito com algumas limitações
- Muito estável e confiável
- Suporta domínio personalizado (plano pago)

**Instalação:**
```powershell
# Baixar ngrok de https://ngrok.com/download
# Extrair para uma pasta (ex: C:\tools\ngrok)
# Adicionar ao PATH ou usar caminho completo
```

**Uso:**
```powershell
# Túnel único para todos os endpoints (recomendado)
ngrok http 8000

# Ou múltiplos túneis (requer conta paga)
ngrok http 8000 --domain=rodust-webhooks.ngrok.io
```

**URLs geradas:**
- `https://xxxx-xx-xx-xx-xx.ngrok-free.app/api/webhooks/mercadopago`
- `https://xxxx-xx-xx-xx-xx.ngrok-free.app/api/melhor-envio/oauth/callback`
- `https://xxxx-xx-xx-xx-xx.ngrok-free.app/api/melhor-envio/webhook`
- `https://xxxx-xx-xx-xx-xx.ngrok-free.app/api/webhooks/bling`

### 2. **Cloudflare Tunnel (cloudflared)** (Gratuito)

**Vantagens:**
- Totalmente gratuito
- Sem limites de tráfego
- Domínio personalizado gratuito
- Muito rápido e confiável
- Suporta múltiplos túneis

**Instalação:**
```powershell
# Baixar de https://github.com/cloudflare/cloudflared/releases
# Extrair cloudflared.exe para uma pasta
```

**Uso:**
```powershell
# Túnel único
cloudflared tunnel --url http://localhost:8000

# Com domínio personalizado (requer configuração no Cloudflare)
cloudflared tunnel --url http://localhost:8000 --hostname webhooks.rodust.com.br
```

### 3. **Solução Própria com Proxy Reverso**

**Arquitetura:**
```
Internet → Seu Servidor (Nginx/Caddy) → Laravel (localhost:8000)
```

**Vantagens:**
- Controle total
- Sem dependências externas
- Domínio próprio
- Sem limites

**Requisitos:**
- Servidor com IP público
- Domínio configurado
- Certificado SSL (Let's Encrypt gratuito)

**Configuração Nginx:**
```nginx
server {
    listen 443 ssl;
    server_name webhooks.rodust.com.br;
    
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    
    location / {
        proxy_pass http://localhost:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

### 4. **LocalTunnel** (Node.js)

**Vantagens:**
- Gratuito
- Open source
- Simples de usar

**Instalação:**
```powershell
npm install -g localtunnel
```

**Uso:**
```powershell
lt --port 8000 --subdomain rodust-webhooks
```

## Recomendação

Para desenvolvimento local, recomendo **ngrok** ou **Cloudflare Tunnel**:
- Ambos são gratuitos
- Suportam múltiplos endpoints simultaneamente
- Muito estáveis
- Fáceis de configurar

Para produção, recomendo a **Solução Própria** com proxy reverso:
- Controle total
- Sem dependências de terceiros
- Domínio próprio
- Melhor performance

## Script PowerShell para ngrok

Criar `ngrok-start.ps1`:

```powershell
# Iniciar ngrok
$ngrokPath = "C:\tools\ngrok\ngrok.exe"  # Ajustar caminho
& $ngrokPath http 8000

# URLs serão exibidas no terminal e disponíveis em http://localhost:4040
```

## Script PowerShell para Cloudflare Tunnel

Criar `cloudflared-start.ps1`:

```powershell
# Iniciar Cloudflare Tunnel
$cloudflaredPath = "C:\tools\cloudflared\cloudflared.exe"  # Ajustar caminho
& $cloudflaredPath tunnel --url http://localhost:8000
```

