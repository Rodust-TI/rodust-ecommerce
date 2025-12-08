# Script para iniciar o UltraHook em background
# Cria um tunel para webhooks do Mercado Pago, Melhor Envio e Bling

Write-Host "=== Iniciando UltraHook ===" -ForegroundColor Cyan
Write-Host ""

# Verificar se UltraHook esta instalado
$ultrahookInstalled = Get-Command ultrahook -ErrorAction SilentlyContinue

if (-not $ultrahookInstalled) {
    Write-Host "[ERRO] UltraHook nao esta instalado!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Execute primeiro:" -ForegroundColor Yellow
    Write-Host "   .\ultrahook-setup.ps1" -ForegroundColor Gray
    exit 1
}

# Verificar se ja existe um processo UltraHook rodando
$existingProcess = Get-Process | Where-Object { $_.ProcessName -like "*ruby*" } -ErrorAction SilentlyContinue

if ($existingProcess) {
    Write-Host "[AVISO] UltraHook ja esta em execucao!" -ForegroundColor Yellow
    Write-Host "   Para parar, execute: .\ultrahook-stop.ps1" -ForegroundColor Gray
    Write-Host ""
    $continue = Read-Host "Deseja parar e reiniciar? (S/N)"
    if ($continue -eq "S" -or $continue -eq "s") {
        Write-Host "Parando processo existente..." -ForegroundColor Yellow
        .\ultrahook-stop.ps1
        Start-Sleep -Seconds 2
    } else {
        Write-Host "Mantendo processo existente." -ForegroundColor Gray
        exit 0
    }
}

# Configuracoes
$namespace = "sanozukez"
$logFile = "ultrahook.log"

# Endpoints configurados
$endpoints = @(
    @{
        name = "Mercado Pago"
        path = "mercadopago"
        localUrl = "http://localhost:8000"
        # UltraHook preserva o path da URL publica automaticamente
        # URL publica: https://sanozukez-mercadopago.ultrahook.com/api/webhooks/mercadopago
        # URL local: http://localhost:8000/api/webhooks/mercadopago (path preservado)
    },
    @{
        name = "Melhor Envio OAuth"
        path = "melhorenvio-oauth"
        localUrl = "http://localhost:8000"
        # UltraHook preserva o path automaticamente
        # URL publica: https://sanozukez-melhorenvio-oauth.ultrahook.com/api/melhor-envio/oauth/callback
        # URL local: http://localhost:8000/api/melhor-envio/oauth/callback (path preservado)
    },
    @{
        name = "Melhor Envio Webhook"
        path = "melhorenvio-webhook"
        localUrl = "http://localhost:8000"
        # UltraHook preserva o path automaticamente
        # URL publica: https://sanozukez-melhorenvio-webhook.ultrahook.com/api/melhor-envio/webhook
        # URL local: http://localhost:8000/api/melhor-envio/webhook (path preservado)
    },
    @{
        name = "Bling Webhook"
        path = "rodust-ecommerce"
        localUrl = "http://localhost:8000"
        # UltraHook preserva o path automaticamente
        # URL publica: https://rodust-ecommerce.ultrahook.com/api/webhooks/bling
        # URL local: http://localhost:8000/api/webhooks/bling (path preservado)
    }
)

Write-Host "Configuracoes:" -ForegroundColor Yellow
Write-Host "   Namespace: $namespace" -ForegroundColor Gray
Write-Host "   Endpoints: $($endpoints.Count)" -ForegroundColor Gray
Write-Host "   Log: $logFile" -ForegroundColor Gray
Write-Host ""

# Criar comandos UltraHook para cada endpoint
$commands = @()
foreach ($endpoint in $endpoints) {
    # Para o Bling, usar o alias rodust-ecommerce diretamente
    if ($endpoint.path -eq "rodust-ecommerce") {
        $commands += "ultrahook rodust-ecommerce $($endpoint.localUrl)"
    } elseif ($endpoint.path -eq "mercadopago") {
        # Mercado Pago: usar apenas URL base
        $commands += "ultrahook mercadopago $($endpoint.localUrl)"
    } else {
        $commands += "ultrahook $namespace-$($endpoint.path) $($endpoint.localUrl)"
    }
    Write-Host "   - $($endpoint.name): $($endpoint.localUrl) (path preservado automaticamente)" -ForegroundColor Gray
}
Write-Host ""

Write-Host "Iniciando UltraHook em background..." -ForegroundColor Yellow
Write-Host ""

# Executar cada endpoint em uma janela separada
$processes = @()
foreach ($endpoint in $endpoints) {
    # Todos os endpoints usam apenas a URL base - UltraHook preserva o path automaticamente
    if ($endpoint.path -eq "rodust-ecommerce") {
        # Bling: usar alias rodust-ecommerce diretamente
        $command = "ultrahook rodust-ecommerce $($endpoint.localUrl)"
    } elseif ($endpoint.path -eq "mercadopago") {
        # Mercado Pago: usar apenas URL base
        $command = "ultrahook mercadopago $($endpoint.localUrl)"
    } else {
        # Melhor Envio: usar namespace-path com URL base
        $command = "ultrahook $namespace-$($endpoint.path) $($endpoint.localUrl)"
    }
    Write-Host "Iniciando: $($endpoint.name)..." -ForegroundColor Cyan
    
    # Executar comando UltraHook (avisos do registry sao apenas informativos e podem ser ignorados)
    $process = Start-Process -FilePath "powershell" `
        -ArgumentList "-NoExit", "-Command", "& { Write-Host '=== $($endpoint.name) ===' -ForegroundColor Green; $command | Tee-Object -FilePath '$logFile' }" `
        -WindowStyle Minimized `
        -PassThru
    
    if ($process) {
        $processes += @{
            Process = $process
            Endpoint = $endpoint
        }
        Start-Sleep -Seconds 2
    }
}

if ($processes.Count -gt 0) {
    Write-Host "[OK] UltraHook iniciado para $($processes.Count) endpoint(s)!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Aguardando URLs dos tunnels..." -ForegroundColor Yellow
    Start-Sleep -Seconds 10
    
    Write-Host ""
    Write-Host "=== URLs dos Tunnels ===" -ForegroundColor Green
    Write-Host ""
    
    # Tentar ler as URLs do log
    if (Test-Path $logFile) {
        $logContent = Get-Content $logFile -Tail 50 -ErrorAction SilentlyContinue
        
        foreach ($endpoint in $endpoints) {
            # Para o Bling, usar o alias rodust-ecommerce
            if ($endpoint.path -eq "rodust-ecommerce") {
                $urlPattern = "rodust-ecommerce"
            } elseif ($endpoint.path -eq "mercadopago") {
                # Mercado Pago: URL esperada pode ser apenas namespace ou namespace-path
                # Verificar no log qual URL foi realmente criada
                $urlPattern = "$namespace-mercadopago"
            } else {
                $urlPattern = "$namespace-$($endpoint.path)"
            }
            $urlLine = $logContent | Where-Object { $_ -like "*$urlPattern*" -or $_ -like "*$($endpoint.name)*" }
            
            Write-Host "[$($endpoint.name)]" -ForegroundColor Cyan
            if ($urlLine) {
                foreach ($line in $urlLine) {
                    if ($line -match "https?://[^\s]+") {
                        $url = $matches[0]
                        Write-Host "   $url" -ForegroundColor White
                    }
                }
            } else {
                $expectedUrl = "https://$urlPattern.ultrahook.com"
                Write-Host "   $expectedUrl" -ForegroundColor Gray
                Write-Host "   (aguardando confirmacao...)" -ForegroundColor Yellow
            }
            Write-Host ""
        }
    }
    
    Write-Host "=== Configuracoes ===" -ForegroundColor Green
    Write-Host ""
    Write-Host "Mercado Pago:" -ForegroundColor Yellow
    Write-Host "   https://www.mercadopago.com.br/developers/panel/app/YOUR_APP_ID/webhooks" -ForegroundColor Cyan
    Write-Host "   Webhook URL (verifique no log qual URL foi criada):" -ForegroundColor Cyan
    Write-Host "      Opcao 1: https://$namespace.ultrahook.com/api/webhooks/mercadopago" -ForegroundColor White
    Write-Host "      Opcao 2: https://$namespace-mercadopago.ultrahook.com/api/webhooks/mercadopago" -ForegroundColor White
    Write-Host "   Redirect URL: https://localhost:8443/pagamento-confirmado" -ForegroundColor White
    Write-Host "   NOTA: Se precisar da URL sem hifen, configure um alias no UltraHook manualmente" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Melhor Envio (OAuth Callback):" -ForegroundColor Yellow
    Write-Host "   Configure no painel Melhor Envio:" -ForegroundColor Cyan
    Write-Host "   URL: https://$namespace-melhorenvio-oauth.ultrahook.com/api/melhor-envio/oauth/callback" -ForegroundColor White
    Write-Host ""
    Write-Host "Melhor Envio (Webhook):" -ForegroundColor Yellow
    Write-Host "   Configure no painel Melhor Envio:" -ForegroundColor Cyan
    Write-Host "   URL: https://$namespace-melhorenvio-webhook.ultrahook.com/api/melhor-envio/webhook" -ForegroundColor White
    Write-Host ""
    Write-Host "Bling (Webhook):" -ForegroundColor Yellow
    Write-Host "   Configure no painel do Bling:" -ForegroundColor Cyan
    Write-Host "   URL: https://$namespace-rodust-ecommerce.ultrahook.com/api/webhooks/bling" -ForegroundColor White
    Write-Host ""
    
    Write-Host "Para ver os logs em tempo real:" -ForegroundColor Cyan
    Write-Host "   Get-Content $logFile -Wait -Tail 20" -ForegroundColor Gray
    Write-Host ""
    Write-Host "Para parar o UltraHook:" -ForegroundColor Cyan
    Write-Host "   .\ultrahook-stop.ps1" -ForegroundColor Gray
    Write-Host ""
    Write-Host "Nota: Cada endpoint esta rodando em uma janela minimizada do PowerShell." -ForegroundColor Yellow
    Write-Host "      Voce pode restaura-las para ver os logs em tempo real." -ForegroundColor Yellow
} else {
    Write-Host "[ERRO] Erro ao iniciar UltraHook!" -ForegroundColor Red
    exit 1
}
