# Script para parar containers Docker do projeto
# Sempre usa a pasta correta: M:\Websites\rodust.com.br\docker

Write-Host "🛑 Parando containers Docker..." -ForegroundColor Yellow
Write-Host ""

Set-Location "M:\Websites\rodust.com.br\docker"

docker-compose down

Write-Host ""
Write-Host "✅ Containers parados!" -ForegroundColor Green
