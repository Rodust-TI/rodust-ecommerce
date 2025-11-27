<#
.SYNOPSIS
    Migra WordPress do XAMPP para Docker

.DESCRIPTION
    Este script:
    1. Copia arquivos do WordPress do XAMPP para a pasta do projeto
    2. Exporta banco de dados do MySQL do XAMPP
    3. Importa banco de dados no MySQL do Docker
    4. Atualiza wp-config.php com credenciais do Docker
    5. Atualiza URLs no banco (de localhost/wordpress para localhost:8080)

.PARAMETER XamppWordPressPath
    Caminho completo da instalação WordPress no XAMPP
    Exemplo: C:\xampp\htdocs\wordpress

.PARAMETER XamppMySQLPort
    Porta do MySQL no XAMPP (padrão: 3306)

.PARAMETER XamppMySQLUser
    Usuário do MySQL no XAMPP (padrão: root)

.PARAMETER XamppMySQLPassword
    Senha do MySQL no XAMPP (deixe vazio se não tiver)

.PARAMETER WordPressDBName
    Nome do banco de dados do WordPress no XAMPP (padrão: wordpress)

.EXAMPLE
    .\migrate-xampp-to-docker.ps1 -XamppWordPressPath "C:\xampp\htdocs\wordpress"

.EXAMPLE
    .\migrate-xampp-to-docker.ps1 -XamppWordPressPath "C:\xampp\htdocs\rodust" -WordPressDBName "rodust_db"
#>

param(
    [Parameter(Mandatory=$true)]
    [string]$XamppWordPressPath,
    
    [Parameter(Mandatory=$false)]
    [int]$XamppMySQLPort = 3306,
    
    [Parameter(Mandatory=$false)]
    [string]$XamppMySQLUser = "root",
    
    [Parameter(Mandatory=$false)]
    [string]$XamppMySQLPassword = "",
    
    [Parameter(Mandatory=$false)]
    [string]$WordPressDBName = "wordpress"
)

# Configurações
$ProjectRoot = Split-Path -Parent $PSScriptRoot
$WordPressDestination = Join-Path (Split-Path -Parent $ProjectRoot) "wordpress"
$BackupDir = Join-Path $ProjectRoot "backups"
$Timestamp = Get-Date -Format "yyyyMMdd_HHmmss"

Write-Host "🚀 MIGRAÇÃO WORDPRESS: XAMPP → DOCKER" -ForegroundColor Cyan
Write-Host "=" * 60 -ForegroundColor DarkGray
Write-Host ""

# ========================================
# VALIDAÇÕES
# ========================================

Write-Host "🔍 Validando ambiente..." -ForegroundColor Yellow

# Verificar se o caminho do WordPress existe
if (-not (Test-Path $XamppWordPressPath)) {
    Write-Host "❌ ERRO: Caminho do WordPress não encontrado: $XamppWordPressPath" -ForegroundColor Red
    exit 1
}

# Verificar se é realmente um WordPress
$wpConfigPath = Join-Path $XamppWordPressPath "wp-config.php"
if (-not (Test-Path $wpConfigPath)) {
    Write-Host "❌ ERRO: wp-config.php não encontrado. Certifique-se que o caminho é de uma instalação WordPress válida." -ForegroundColor Red
    exit 1
}

# Verificar se Docker está rodando
try {
    $dockerStatus = docker ps 2>&1
    if ($LASTEXITCODE -ne 0) {
        Write-Host "❌ ERRO: Docker não está rodando. Inicie o Docker Desktop e tente novamente." -ForegroundColor Red
        exit 1
    }
} catch {
    Write-Host "❌ ERRO: Docker não encontrado. Instale o Docker Desktop." -ForegroundColor Red
    exit 1
}

# Verificar se mysqldump existe (vem com XAMPP)
$mysqldumpPath = "C:\xampp\mysql\bin\mysqldump.exe"
if (-not (Test-Path $mysqldumpPath)) {
    Write-Host "❌ ERRO: mysqldump não encontrado no XAMPP: $mysqldumpPath" -ForegroundColor Red
    Write-Host "   Informe o caminho correto ou instale o MySQL client." -ForegroundColor Yellow
    exit 1
}

Write-Host "✅ Validações concluídas!" -ForegroundColor Green
Write-Host ""

# ========================================
# PASSO 1: BACKUP
# ========================================

Write-Host "📦 PASSO 1/5: Criando backup do WordPress atual..." -ForegroundColor Cyan

# Criar diretório de backup
if (-not (Test-Path $BackupDir)) {
    New-Item -ItemType Directory -Path $BackupDir -Force | Out-Null
}

$BackupFile = Join-Path $BackupDir "wordpress_backup_$Timestamp.zip"

Write-Host "   Comprimindo arquivos do WordPress..." -ForegroundColor Gray
try {
    Compress-Archive -Path $XamppWordPressPath -DestinationPath $BackupFile -Force
    Write-Host "   ✅ Backup salvo em: $BackupFile" -ForegroundColor Green
} catch {
    Write-Host "   ⚠️ Aviso: Não foi possível criar backup dos arquivos: $_" -ForegroundColor Yellow
}

Write-Host ""

# ========================================
# PASSO 2: EXPORTAR BANCO DE DADOS
# ========================================

Write-Host "💾 PASSO 2/5: Exportando banco de dados do XAMPP..." -ForegroundColor Cyan

$SQLDumpFile = Join-Path $BackupDir "wordpress_db_$Timestamp.sql"

# Construir comando mysqldump
$mysqldumpArgs = @(
    "-h", "127.0.0.1",
    "-P", $XamppMySQLPort,
    "-u", $XamppMySQLUser
)

if ($XamppMySQLPassword) {
    $mysqldumpArgs += "-p$XamppMySQLPassword"
}

$mysqldumpArgs += @(
    "--single-transaction",
    "--quick",
    "--lock-tables=false",
    $WordPressDBName
)

Write-Host "   Executando mysqldump..." -ForegroundColor Gray

try {
    & $mysqldumpPath $mysqldumpArgs | Out-File -FilePath $SQLDumpFile -Encoding UTF8
    
    if ($LASTEXITCODE -eq 0) {
        $fileSize = (Get-Item $SQLDumpFile).Length / 1MB
        Write-Host "   ✅ Banco exportado: $SQLDumpFile ($([math]::Round($fileSize, 2)) MB)" -ForegroundColor Green
    } else {
        Write-Host "   ❌ ERRO: Falha ao exportar banco de dados (Exit Code: $LASTEXITCODE)" -ForegroundColor Red
        exit 1
    }
} catch {
    Write-Host "   ❌ ERRO: $_" -ForegroundColor Red
    exit 1
}

Write-Host ""

# ========================================
# PASSO 3: COPIAR ARQUIVOS
# ========================================

Write-Host "📁 PASSO 3/5: Copiando arquivos do WordPress..." -ForegroundColor Cyan

# Criar diretório destino se não existir
if (-not (Test-Path $WordPressDestination)) {
    New-Item -ItemType Directory -Path $WordPressDestination -Force | Out-Null
}

Write-Host "   De: $XamppWordPressPath" -ForegroundColor Gray
Write-Host "   Para: $WordPressDestination" -ForegroundColor Gray

try {
    # Copiar todos os arquivos exceto wp-config.php (será gerado novo)
    $itemsToCopy = Get-ChildItem -Path $XamppWordPressPath -Exclude "wp-config.php"
    
    foreach ($item in $itemsToCopy) {
        $dest = Join-Path $WordPressDestination $item.Name
        
        if ($item.PSIsContainer) {
            Copy-Item -Path $item.FullName -Destination $dest -Recurse -Force
        } else {
            Copy-Item -Path $item.FullName -Destination $dest -Force
        }
    }
    
    Write-Host "   ✅ Arquivos copiados com sucesso!" -ForegroundColor Green
} catch {
    Write-Host "   ❌ ERRO ao copiar arquivos: $_" -ForegroundColor Red
    exit 1
}

Write-Host ""

# ========================================
# PASSO 4: INICIAR DOCKER E IMPORTAR DB
# ========================================

Write-Host "🐳 PASSO 4/5: Iniciando containers Docker..." -ForegroundColor Cyan

Set-Location $ProjectRoot

Write-Host "   Parando containers antigos..." -ForegroundColor Gray
docker compose down 2>&1 | Out-Null

Write-Host "   Iniciando containers..." -ForegroundColor Gray
docker compose up -d

Write-Host "   Aguardando MySQL inicializar (30s)..." -ForegroundColor Gray
Start-Sleep -Seconds 30

# Importar banco de dados
Write-Host "   Importando banco de dados para o Docker..." -ForegroundColor Gray

try {
    Get-Content $SQLDumpFile | docker compose exec -T mysql mysql -uroot -ppassword wordpress
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "   ✅ Banco importado com sucesso!" -ForegroundColor Green
    } else {
        Write-Host "   ❌ ERRO ao importar banco (Exit Code: $LASTEXITCODE)" -ForegroundColor Red
        Write-Host "   Tentando criar banco manualmente..." -ForegroundColor Yellow
        
        docker compose exec mysql mysql -uroot -ppassword -e "CREATE DATABASE IF NOT EXISTS wordpress;"
        Get-Content $SQLDumpFile | docker compose exec -T mysql mysql -uroot -ppassword wordpress
    }
} catch {
    Write-Host "   ❌ ERRO: $_" -ForegroundColor Red
    exit 1
}

Write-Host ""

# ========================================
# PASSO 5: ATUALIZAR URLs NO BANCO
# ========================================

Write-Host "🔧 PASSO 5/5: Atualizando URLs no banco de dados..." -ForegroundColor Cyan

# Detectar URL antiga do WordPress no XAMPP
$oldUrlPattern = @"
SELECT option_value FROM wp_options WHERE option_name = 'siteurl' LIMIT 1;
"@

$oldUrl = docker compose exec -T mysql mysql -uroot -ppassword -D wordpress -N -e $oldUrlPattern 2>&1 | Select-Object -Last 1
$oldUrl = $oldUrl.Trim()

if ($oldUrl) {
    Write-Host "   URL antiga detectada: $oldUrl" -ForegroundColor Gray
    Write-Host "   URL nova: http://localhost:8080" -ForegroundColor Gray
    
    # SQL para atualizar URLs
    $updateSQL = @"
UPDATE wp_options SET option_value = 'http://localhost:8080' WHERE option_name = 'siteurl';
UPDATE wp_options SET option_value = 'http://localhost:8080' WHERE option_name = 'home';
UPDATE wp_posts SET post_content = REPLACE(post_content, '$oldUrl', 'http://localhost:8080');
UPDATE wp_posts SET guid = REPLACE(guid, '$oldUrl', 'http://localhost:8080');
UPDATE wp_postmeta SET meta_value = REPLACE(meta_value, '$oldUrl', 'http://localhost:8080');
"@
    
    docker compose exec -T mysql mysql -uroot -ppassword -D wordpress -e $updateSQL
    
    Write-Host "   ✅ URLs atualizadas!" -ForegroundColor Green
} else {
    Write-Host "   ⚠️ Não foi possível detectar URL antiga. Atualize manualmente no wp-admin." -ForegroundColor Yellow
}

Write-Host ""

# ========================================
# FINALIZAÇÃO
# ========================================

Write-Host "=" * 60 -ForegroundColor DarkGray
Write-Host "✅ MIGRAÇÃO CONCLUÍDA COM SUCESSO!" -ForegroundColor Green -BackgroundColor DarkGreen
Write-Host "=" * 60 -ForegroundColor DarkGray
Write-Host ""

Write-Host "📋 PRÓXIMOS PASSOS:" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. Acesse o WordPress:" -ForegroundColor White
Write-Host "   🌐 HTTP:  http://localhost:8080" -ForegroundColor Yellow
Write-Host "   🔒 HTTPS: https://localhost:8443 (aceitar certificado self-signed)" -ForegroundColor Yellow
Write-Host ""
Write-Host "2. Faça login no wp-admin:" -ForegroundColor White
Write-Host "   http://localhost:8080/wp-admin" -ForegroundColor Yellow
Write-Host ""
Write-Host "3. Crie Application Password:" -ForegroundColor White
Write-Host "   - Vá em: Usuários → Perfil" -ForegroundColor Gray
Write-Host "   - Role até 'Application Passwords'" -ForegroundColor Gray
Write-Host "   - Digite nome: 'Laravel API'" -ForegroundColor Gray
Write-Host "   - Clique 'Add New Application Password'" -ForegroundColor Gray
Write-Host "   - Copie a senha gerada" -ForegroundColor Gray
Write-Host ""
Write-Host "4. Configure .env do Laravel:" -ForegroundColor White
Write-Host "   Adicione as linhas no arquivo .env:" -ForegroundColor Gray
Write-Host "   WORDPRESS_URL=https://localhost:8443" -ForegroundColor Yellow
Write-Host "   WORDPRESS_API_USER=seu_usuario" -ForegroundColor Yellow
Write-Host "   WORDPRESS_API_PASSWORD=xxxx xxxx xxxx xxxx xxxx xxxx" -ForegroundColor Yellow
Write-Host ""
Write-Host "5. Teste a sincronização:" -ForegroundColor White
Write-Host "   docker compose exec laravel.test php artisan queue:work" -ForegroundColor Yellow
Write-Host "   curl -X POST http://localhost:8000/api/products/sync-to-wordpress" -ForegroundColor Yellow
Write-Host ""
Write-Host "📦 Backups salvos em:" -ForegroundColor Cyan
Write-Host "   $BackupDir" -ForegroundColor Gray
Write-Host ""
Write-Host "🎉 Boa sorte com seu projeto!" -ForegroundColor Magenta
