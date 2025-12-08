# Script para instalar e configurar UltraHook
# UltraHook é usado para criar túneis para webhooks locais

Write-Host "=== Configuração do UltraHook ===" -ForegroundColor Cyan
Write-Host ""

# Verificar se Ruby está instalado
Write-Host "Verificando Ruby..." -ForegroundColor Yellow
$rubyInstalled = Get-Command ruby -ErrorAction SilentlyContinue

if (-not $rubyInstalled) {
    Write-Host "❌ Ruby não está instalado!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Para instalar Ruby no Windows:" -ForegroundColor Yellow
    Write-Host "   1. Baixe o RubyInstaller: https://rubyinstaller.org/" -ForegroundColor Gray
    Write-Host "   2. Execute o instalador" -ForegroundColor Gray
    Write-Host "   3. Execute este script novamente" -ForegroundColor Gray
    Write-Host ""
    Write-Host "Ou use o Chocolatey:" -ForegroundColor Yellow
    Write-Host "   choco install ruby" -ForegroundColor Gray
    exit 1
}

Write-Host "✅ Ruby encontrado: $(ruby --version)" -ForegroundColor Green
Write-Host ""

# Verificar se gem está disponível
Write-Host "Verificando gem (Ruby package manager)..." -ForegroundColor Yellow
$gemInstalled = Get-Command gem -ErrorAction SilentlyContinue

if (-not $gemInstalled) {
    Write-Host "❌ gem não está disponível!" -ForegroundColor Red
    exit 1
}

Write-Host "✅ gem encontrado" -ForegroundColor Green
Write-Host ""

# Instalar UltraHook
Write-Host "Instalando UltraHook..." -ForegroundColor Yellow
gem install ultrahook

if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Erro ao instalar UltraHook!" -ForegroundColor Red
    Write-Host "   Tente executar manualmente: gem install ultrahook" -ForegroundColor Gray
    exit 1
}

Write-Host "✅ UltraHook instalado com sucesso!" -ForegroundColor Green
Write-Host ""

# Configurar API key
Write-Host "Configurando API key..." -ForegroundColor Yellow
$apiKey = "b63Gak2wTfw5sezzeAuD9nK2gXI5wxNK"
$ultrahookConfigPath = "$env:USERPROFILE\.ultrahook"

# Criar arquivo de configuração
$configContent = "api_key: $apiKey"
$configContent | Out-File -FilePath $ultrahookConfigPath -Encoding UTF8 -Force

Write-Host "✅ API key configurada em: $ultrahookConfigPath" -ForegroundColor Green
Write-Host ""

Write-Host "=== Configuração concluída! ===" -ForegroundColor Green
Write-Host ""
Write-Host "Para iniciar o tunnel de webhooks, execute:" -ForegroundColor Cyan
Write-Host "   .\ultrahook-start.ps1" -ForegroundColor Yellow
Write-Host ""

