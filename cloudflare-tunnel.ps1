# cloudflare-tunnel.ps1
# Script para iniciar Cloudflare Tunnel para desenvolvimento Laravel

param(
    [int]$Port = 8000,
    [string]$Protocol = "http"
)

Write-Host ""
Write-Host "🌐 Cloudflare Tunnel - Rodust Ecommerce" -ForegroundColor Cyan
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor DarkGray
Write-Host "   Porta: $Port" -ForegroundColor Gray
Write-Host "   Protocolo: $Protocol" -ForegroundColor Gray
Write-Host ""

# Verificar se cloudflared está instalado
if (-not (Get-Command cloudflared -ErrorAction SilentlyContinue)) {
    Write-Host "❌ cloudflared não encontrado!" -ForegroundColor Red
    Write-Host ""
    Write-Host "📥 Como instalar:" -ForegroundColor Yellow
    Write-Host "   1. Via Chocolatey (recomendado):" -ForegroundColor White
    Write-Host "      choco install cloudflared" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "   2. Ou baixe manualmente:" -ForegroundColor White
    Write-Host "      https://github.com/cloudflare/cloudflared/releases" -ForegroundColor Cyan
    Write-Host ""
    exit 1
}

$version = cloudflared --version
Write-Host "✅ cloudflared instalado: $version" -ForegroundColor Green
Write-Host ""

Write-Host "📡 Iniciando túnel..." -ForegroundColor Yellow
Write-Host ""
Write-Host "⚠️  Aguarde alguns segundos para a URL aparecer..." -ForegroundColor Yellow
Write-Host "💡 Quando aparecer a URL, configure no MercadoPago:" -ForegroundColor Cyan
Write-Host "   https://SUA-URL.trycloudflare.com/api/webhooks/mercadopago" -ForegroundColor White
Write-Host ""
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor DarkGray
Write-Host ""

# Iniciar túnel
cloudflared tunnel --url "${Protocol}://localhost:${Port}"
