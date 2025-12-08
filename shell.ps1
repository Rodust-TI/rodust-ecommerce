# Script para acessar o shell do container Laravel
# Uso: .\shell.ps1

Write-Host "🐚 Acessando shell do container Laravel..." -ForegroundColor Cyan
Write-Host ""

docker exec -it docker-laravel.test-1 bash
