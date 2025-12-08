# Script para executar comandos artisan no container correto
# Uso: .\artisan.ps1 migrate
# Uso: .\artisan.ps1 "migrate --seed"
# Uso: .\artisan.ps1 tinker

param(
    [Parameter(Mandatory=$true, Position=0, ValueFromRemainingArguments=$true)]
    [string[]]$Command
)

$fullCommand = $Command -join " "

Write-Host "🚀 Executando: php artisan $fullCommand" -ForegroundColor Cyan
Write-Host ""

docker exec -it docker-laravel.test-1 php artisan $fullCommand
