# Script para parar o UltraHook

Write-Host "=== Parando UltraHook ===" -ForegroundColor Cyan
Write-Host ""

# Procurar processos PowerShell que estao executando UltraHook
$powershellProcesses = Get-Process | Where-Object { 
    $_.ProcessName -eq "powershell" -or $_.ProcessName -eq "pwsh"
} -ErrorAction SilentlyContinue

# Procurar processos Ruby que estao executando UltraHook
$rubyProcesses = Get-Process | Where-Object { 
    $_.ProcessName -like "*ruby*"
} -ErrorAction SilentlyContinue

$found = $false

# Parar processos PowerShell relacionados ao UltraHook
if ($powershellProcesses) {
    foreach ($proc in $powershellProcesses) {
        try {
            $commandLine = (Get-CimInstance Win32_Process -Filter "ProcessId = $($proc.Id)").CommandLine
            if ($commandLine -like "*ultrahook*") {
                $found = $true
                Write-Host "Processo PowerShell encontrado (PID: $($proc.Id))" -ForegroundColor Yellow
                Stop-Process -Id $proc.Id -Force -ErrorAction Stop
                Write-Host "[OK] Processo $($proc.Id) parado" -ForegroundColor Green
            }
        } catch {
            # Ignorar erros ao verificar processos
        }
    }
}

# Parar processos Ruby relacionados ao UltraHook
if ($rubyProcesses) {
    $found = $true
    Write-Host "Processos Ruby encontrados:" -ForegroundColor Yellow
    foreach ($proc in $rubyProcesses) {
        Write-Host "   PID: $($proc.Id) - $($proc.ProcessName)" -ForegroundColor Gray
    }
    Write-Host ""
    
    $confirm = Read-Host "Deseja parar todos os processos Ruby? (S/N)"
    
    if ($confirm -eq "S" -or $confirm -eq "s") {
        foreach ($proc in $rubyProcesses) {
            try {
                Stop-Process -Id $proc.Id -Force -ErrorAction Stop
                Write-Host "[OK] Processo $($proc.Id) parado" -ForegroundColor Green
            } catch {
                Write-Host "[AVISO] Erro ao parar processo $($proc.Id): $_" -ForegroundColor Yellow
            }
        }
    } else {
        Write-Host "Processos mantidos em execucao." -ForegroundColor Yellow
    }
}

if (-not $found) {
    Write-Host "[INFO] Nenhum processo UltraHook encontrado." -ForegroundColor Yellow
    exit 0
}

Write-Host ""
Write-Host "[OK] UltraHook parado!" -ForegroundColor Green
