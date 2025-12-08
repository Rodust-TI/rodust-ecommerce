# Cloudflare Tunnel - Alternativa Gratuita e Estável ao LocalTunnel

## 🚀 Por Que Usar Cloudflare Tunnel?

- ✅ **Totalmente GRATUITO** (sem cartão de crédito)
- ✅ **Mais estável** que LocalTunnel
- ✅ **Mais rápido** (infraestrutura da Cloudflare)
- ✅ **URLs personalizadas** opcionais
- ✅ **Sem limite de tempo** (LocalTunnel cai muito)
- ✅ **Sem criar conta Cloudflare** (modo anônimo)

---

## 📥 Instalação

### Windows (PowerShell)

```powershell
# Baixar cloudflared
Invoke-WebRequest -Uri "https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe" -OutFile "cloudflared.exe"

# Mover para pasta acessível
Move-Item cloudflared.exe C:\Windows\System32\cloudflared.exe

# Verificar instalação
cloudflared --version
```

Ou instalar via Chocolatey (recomendado):

```powershell
choco install cloudflared
```

---

## 🔧 Uso Básico (Sem Conta/Cartão)

### Criar túnel temporário (modo anônimo)

```powershell
# Expor porta 8000 (Laravel)
cloudflared tunnel --url http://localhost:8000
```

**Resultado:**
```
2025-12-02T10:30:00Z INF Thank you for trying Cloudflare Tunnel. Doing so, without a Cloudflare account, is a quick way to experiment and try it out. However, be aware that these account-less tunnels have no uptime guarantee.
2025-12-02T10:30:00Z INF +--------------------------------------------------------------------------------------------+
2025-12-02T10:30:00Z INF |  Your quick Tunnel has been created! Visit it at (it may take some time to be reachable):  |
2025-12-02T10:30:00Z INF |  https://random-subdomain-xyz.trycloudflare.com                                            |
2025-12-02T10:30:00Z INF +--------------------------------------------------------------------------------------------+
```

### Copiar a URL e configurar no MercadoPago

```
https://random-subdomain-xyz.trycloudflare.com/api/webhooks/mercadopago
```

---

## 🎯 Script PowerShell para Facilitar

Crie o arquivo `cloudflare-tunnel.ps1` no seu projeto:

```powershell
# cloudflare-tunnel.ps1
# Script para iniciar Cloudflare Tunnel para desenvolvimento

param(
    [int]$Port = 8000,
    [string]$Protocol = "http"
)

Write-Host "🌐 Iniciando Cloudflare Tunnel..." -ForegroundColor Cyan
Write-Host "   Porta: $Port" -ForegroundColor Gray
Write-Host "   Protocolo: $Protocol" -ForegroundColor Gray
Write-Host ""

# Verificar se cloudflared está instalado
if (-not (Get-Command cloudflared -ErrorAction SilentlyContinue)) {
    Write-Host "❌ cloudflared não encontrado!" -ForegroundColor Red
    Write-Host "   Instale com: choco install cloudflared" -ForegroundColor Yellow
    Write-Host "   Ou baixe de: https://github.com/cloudflare/cloudflared/releases" -ForegroundColor Yellow
    exit 1
}

Write-Host "✅ cloudflared encontrado" -ForegroundColor Green
Write-Host ""
Write-Host "📡 Criando túnel..." -ForegroundColor Yellow
Write-Host ""
Write-Host "⚠️  Aguarde a URL aparecer abaixo..." -ForegroundColor Yellow
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor DarkGray
Write-Host ""

# Iniciar túnel
cloudflared tunnel --url "${Protocol}://localhost:${Port}"
```

### Como usar:

```powershell
# Expor porta 8000 (padrão)
.\cloudflare-tunnel.ps1

# Expor outra porta
.\cloudflare-tunnel.ps1 -Port 8080

# HTTPS local (se configurado)
.\cloudflare-tunnel.ps1 -Protocol https
```

---

## 🆚 Comparação: LocalTunnel vs Cloudflare Tunnel

| Característica | LocalTunnel | Cloudflare Tunnel |
|----------------|-------------|-------------------|
| **Gratuito** | ✅ Sim | ✅ Sim |
| **Precisa Cartão** | ❌ Não | ❌ Não |
| **Estabilidade** | ⚠️ Baixa (cai muito) | ✅ Alta |
| **Velocidade** | ⚠️ Média | ✅ Rápida |
| **Timeout** | ⚠️ Frequente | ✅ Raro |
| **Reconexão Auto** | ❌ Não | ✅ Sim |
| **Subdomain Fixo** | ✅ Sim (`--subdomain`) | ⚠️ Aleatório (modo grátis) |
| **Facilidade** | ✅ Fácil | ✅ Fácil |

---

## 🔐 Modo Autenticado (Opcional - Sem Cartão)

Se quiser URL fixa e mais recursos, você pode criar conta Cloudflare **SEM cartão**:

1. Criar conta gratuita em https://dash.cloudflare.com/sign-up
2. Não precisa adicionar domínio
3. Fazer login no cloudflared:

```powershell
cloudflared tunnel login
```

4. Criar túnel nomeado:

```powershell
cloudflared tunnel create rodust-dev
cloudflared tunnel route dns rodust-dev rodust-dev.example.com
cloudflared tunnel run rodust-dev
```

**Mas não é necessário para desenvolvimento!** Modo anônimo funciona bem.

---

## 📝 Atualizar Webhook no MercadoPago

Quando o túnel estiver ativo:

1. Copiar a URL que aparece: `https://xyz.trycloudflare.com`
2. Adicionar no `.env`:

```env
MERCADOPAGO_WEBHOOK_URL=https://xyz.trycloudflare.com/api/webhooks/mercadopago
```

3. Configurar no painel do MercadoPago:
   - Acessar: https://www.mercadopago.com.br/developers/panel/app
   - Ir em: Webhooks
   - Adicionar: `https://xyz.trycloudflare.com/api/webhooks/mercadopago`

---

## 🎯 Vantagens para seu Projeto

### LocalTunnel (Atual - Instável)
```powershell
# Cai frequentemente, precisa reiniciar
lt --port 8000 --subdomain rodust-ecommerce-dev
# ⚠️ Connection closed, reconnecting...
# ⚠️ Tunnel died, restarting...
```

### Cloudflare Tunnel (Recomendado - Estável)
```powershell
# Muito mais estável, raramente cai
cloudflared tunnel --url http://localhost:8000
# ✅ Tunnel running smoothly
```

---

## 🚨 Dica Importante

A URL do Cloudflare Tunnel muda **cada vez que você reinicia** no modo anônimo.

**Solução:**
1. Inicie o túnel
2. Copie a URL
3. Atualize no painel do MercadoPago (leva 1 min)

Ou use **conta autenticada** para URL fixa (ainda sem cartão!).

---

## 📚 Links Úteis

- [Cloudflared Releases](https://github.com/cloudflare/cloudflared/releases)
- [Documentação Cloudflare Tunnel](https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/)
- [Quick Tunnels (Anônimo)](https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/do-more-with-tunnels/trycloudflare/)

---

## ✅ Comando Rápido para Começar Agora

```powershell
# Instalar (se não tiver)
choco install cloudflared

# Usar imediatamente
cloudflared tunnel --url http://localhost:8000
```

**Pronto!** Você terá uma URL estável sem precisar de cartão! 🎉

---

**Última atualização:** 02/12/2025
