# Script auxiliar para Laravel Sail no Windows
# Uso: .\sail.ps1 up -d
# Ou crie um alias: function sail { .\sail.ps1 $args }

param(
    [Parameter(ValueFromRemainingArguments=$true)]
    $command
)

# Definir variáveis de ambiente necessárias para Sail
$env:WWWUSER = "1000"
$env:WWWGROUP = "1000"
$env:PWD = Get-Location

# Verificar se WSL está disponível
$wslAvailable = Get-Command wsl -ErrorAction SilentlyContinue

if ($wslAvailable) {
    # Usar WSL para executar o script Sail (melhor compatibilidade)
    $projectPath = (Get-Location).Path -replace '\\', '/' -replace '^([A-Z]):', '/mnt/$1'.ToLower()
    wsl bash -c "cd '$projectPath' && ./vendor/bin/sail $command"
} else {
    # Fallback: usar docker-compose diretamente
    docker compose $command
}
