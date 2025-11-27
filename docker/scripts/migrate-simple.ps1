# Script Simplificado de Migração WordPress
# Use este se o script completo der problemas

Write-Host "🚀 Migração Simplificada WordPress" -ForegroundColor Cyan
Write-Host ""

# 1. Informações necessárias
$xamppPath = Read-Host "Caminho do WordPress no XAMPP (ex: C:\xampp\htdocs\wordpress)"

if (-not (Test-Path "$xamppPath\wp-config.php")) {
    Write-Host "❌ wp-config.php não encontrado em: $xamppPath" -ForegroundColor Red
    exit 1
}

Write-Host "✅ WordPress encontrado!" -ForegroundColor Green

# 2. Copiar arquivos
Write-Host ""
Write-Host "📁 Copiando arquivos..." -ForegroundColor Yellow

$destino = "M:\Websites\rodust.com.br\wordpress"

if (-not (Test-Path $destino)) {
    New-Item -ItemType Directory -Path $destino -Force | Out-Null
}

Copy-Item -Path "$xamppPath\*" -Destination $destino -Recurse -Force

Write-Host "✅ Arquivos copiados!" -ForegroundColor Green

# 3. Instruções para o banco
Write-Host ""
Write-Host "💾 PRÓXIMO PASSO: Exportar banco de dados" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. Abra o phpMyAdmin do XAMPP:" -ForegroundColor White
Write-Host "   http://localhost/phpmyadmin" -ForegroundColor Yellow
Write-Host ""
Write-Host "2. Selecione o banco 'wordpress' (ou o nome do seu banco)" -ForegroundColor White
Write-Host ""
Write-Host "3. Clique em 'Exportar' → 'Executar'" -ForegroundColor White
Write-Host ""
Write-Host "4. Salve o arquivo como: wordpress_export.sql" -ForegroundColor White
Write-Host ""
Write-Host "5. Execute este comando para importar:" -ForegroundColor White
Write-Host ""
Write-Host "   Get-Content wordpress_export.sql | docker compose exec -T mysql mysql -uroot -ppassword wordpress" -ForegroundColor Yellow
Write-Host ""

# 4. Iniciar Docker
Write-Host ""
$iniciar = Read-Host "Deseja iniciar os containers Docker agora? (s/n)"

if ($iniciar -eq "s") {
    Write-Host "🐳 Iniciando Docker..." -ForegroundColor Yellow
    
    Set-Location "M:\Websites\rodust.com.br\ecommerce"
    docker compose down
    docker compose up -d
    
    Write-Host "✅ Containers iniciados!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Aguarde 30 segundos para o MySQL inicializar..." -ForegroundColor Gray
    Start-Sleep -Seconds 30
    
    Write-Host ""
    Write-Host "✅ Pronto! Agora importe o banco conforme instruções acima." -ForegroundColor Green
}

Write-Host ""
Write-Host "📋 Após importar o banco, atualize URLs:" -ForegroundColor Cyan
Write-Host ""
Write-Host @"
docker compose exec mysql mysql -uroot -ppassword -D wordpress -e "
UPDATE wp_options SET option_value='http://localhost:8080' WHERE option_name='siteurl';
UPDATE wp_options SET option_value='http://localhost:8080' WHERE option_name='home';
"
"@ -ForegroundColor Yellow

Write-Host ""
Write-Host "🎉 Depois acesse: http://localhost:8080" -ForegroundColor Green
