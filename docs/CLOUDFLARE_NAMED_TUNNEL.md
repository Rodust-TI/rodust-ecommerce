# Configuração do Túnel Nomeado Cloudflare - rodust_lararvel

## Status Atual
✅ Serviço instalado e rodando automaticamente
✅ Túnel nomeado: `rodust_lararvel`
✅ Integrado ao docker-up.ps1

## Como Configurar o Hostname

### 1. Acessar o Painel Cloudflare
https://one.dash.cloudflare.com/

### 2. Navegar até o Túnel
- Menu lateral: **Zero Trust** ou **Cloudflare One**
- Seção: **Access** > **Tunnels**
- Encontrar: `rodust_lararvel`

### 3. Configurar Public Hostname

Clique no túnel e adicione um **Public Hostname**:

**Opção A: Sem domínio próprio (trycloudflare.com)**
- Subdomain: `rodust-laravel` (ou qualquer nome)
- Domain: `trycloudflare.com`
- Service:
  - Type: `HTTP`
  - URL: `localhost:8000`

**URL Final:** `https://rodust-laravel.trycloudflare.com`

**Opção B: Com domínio próprio** (se tiver rodust.com.br no Cloudflare)
- Subdomain: `api-dev` (ou `ecommerce-dev`)
- Domain: `rodust.com.br`
- Service:
  - Type: `HTTP`
  - URL: `localhost:8000`

**URL Final:** `https://api-dev.rodust.com.br`

### 4. Salvar Configuração

Clique em **Save hostname** ou **Save tunnel**.

O serviço já está rodando, então as mudanças são aplicadas imediatamente (até 30 segundos).

---

## Configurar Webhook no MercadoPago

### Após definir o hostname:

1. **Copie a URL do seu túnel**
   - Ex: `https://rodust-laravel.trycloudflare.com`

2. **Acesse o Painel MercadoPago**
   - https://www.mercadopago.com.br/developers/panel/app

3. **Configure o Webhook**
   - Menu: **Webhooks**
   - URL: `https://rodust-laravel.trycloudflare.com/api/webhooks/mercadopago`
   - Eventos: Selecione **Payments** (pagamentos)

4. **Atualizar .env**
   ```env
   MERCADOPAGO_WEBHOOK_URL=https://rodust-laravel.trycloudflare.com/api/webhooks/mercadopago
   ```

---

## Gerenciar o Serviço

### Verificar Status
```powershell
Get-Service cloudflared
```

### Iniciar
```powershell
Start-Service cloudflared
```

### Parar
```powershell
Stop-Service cloudflared
```

### Reiniciar
```powershell
Restart-Service cloudflared
```

### Ver Logs
```powershell
Get-Content "C:\ProgramData\cloudflared\cloudflared.log" -Tail 50 -Wait
```

---

## Vantagens do Túnel Nomeado

✅ **URL estável** - não muda a cada reinício
✅ **Serviço Windows** - inicia automaticamente com o sistema
✅ **Mais confiável** - infraestrutura empresarial Cloudflare
✅ **Sem janelas extras** - roda em background
✅ **Integrado ao docker-up.ps1** - inicia junto com os containers

---

## Desinstalar (se necessário)

```powershell
# Parar serviço
Stop-Service cloudflared

# Desinstalar serviço
cloudflared.exe service uninstall

# Remover arquivos (opcional)
Remove-Item -Path "C:\ProgramData\cloudflared" -Recurse -Force
```

---

## Troubleshooting

### Túnel não conecta
1. Verificar se o serviço está rodando: `Get-Service cloudflared`
2. Ver logs: `Get-Content "C:\ProgramData\cloudflared\cloudflared.log" -Tail 50`
3. Reiniciar: `Restart-Service cloudflared`

### Erro 502 Bad Gateway
- Laravel não está rodando na porta 8000
- Verificar: `docker ps` e garantir que `docker-laravel.test-1` está UP

### Webhook não recebe notificações
- Verificar URL no painel MercadoPago
- Testar manualmente: `Invoke-WebRequest -Uri "https://SUA-URL/api/webhooks/mercadopago" -Method POST`

---

**Última atualização:** 02/12/2025
