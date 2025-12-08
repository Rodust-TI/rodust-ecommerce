# Script para iniciar containers Docker do projeto
# Sempre usa a pasta correta: M:\Websites\rodust.com.br\docker

Write-Host "Iniciando containers Docker..." -ForegroundColor Cyan
Write-Host ""

Set-Location "M:\Websites\rodust.com.br\docker"

docker-compose up -d

Write-Host ""
Write-Host "Containers iniciados!" -ForegroundColor Green
Write-Host ""
Write-Host "Containers ativos:" -ForegroundColor Yellow
docker ps --format "table {{.Names}}\t{{.Status}}"
Write-Host ""

Write-Host ""
Write-Host "Para executar comandos artisan:" -ForegroundColor Cyan
Write-Host "   docker exec -it docker-laravel.test-1 php artisan [comando]" -ForegroundColor Gray
Write-Host "   Ou use: .\artisan.ps1 [comando]" -ForegroundColor Gray
Write-Host ""
Write-Host "URLs Locais:" -ForegroundColor Cyan
Write-Host "   Laravel: http://localhost:8000" -ForegroundColor Gray
Write-Host "   Painel Admin: http://localhost:8000/admin" -ForegroundColor Gray
Write-Host "   Painel Bling: http://localhost:8000/bling" -ForegroundColor Gray
Write-Host "   WordPress: https://localhost:8443" -ForegroundColor Gray
Write-Host "   MySQL: localhost:3307" -ForegroundColor Gray
Write-Host ""
Write-Host "UltraHook (Webhooks):" -ForegroundColor Cyan
Write-Host "   Para iniciar o tunnel de webhooks, execute:" -ForegroundColor Gray
Write-Host "   .\ultrahook-start.ps1" -ForegroundColor Yellow
Write-Host ""
Write-Host "   A URL do webhook sera exibida quando o UltraHook iniciar." -ForegroundColor Gray
