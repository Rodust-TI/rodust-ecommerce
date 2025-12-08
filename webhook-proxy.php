<?php
/**
 * WEBHOOK PROXY - Mercado Pago → Localhost
 * 
 * INSTRUÇÕES DE USO:
 * 
 * 1. Hospede este arquivo em: https://rodust.com.br/webhook-proxy.php
 * 
 * 2. Configure no Mercado Pago:
 *    URL do Webhook: https://rodust.com.br/webhook-proxy.php
 * 
 * 3. Configure o IP do seu localhost aqui embaixo (pode ser dinâmico via DDNS)
 * 
 * 4. Para produção: apague este arquivo ou comente a linha $FORWARD_TO
 */

// ============================================
// CONFIGURAÇÃO
// ============================================

// URL do seu localhost (altere para seu IP/DDNS se necessário)
// Opções:
// 1. IP Público fixo: http://200.123.45.67:8000/api/webhooks/mercadopago
// 2. DDNS (No-IP, DynDNS): http://seudominio.ddns.net:8000/api/webhooks/mercadopago
// 3. Túnel Cloudflare: https://seu-tunnel.trycloudflare.com/api/webhooks/mercadopago
$FORWARD_TO = 'http://SEU_IP_PUBLICO:8000/api/webhooks/mercadopago'; // <-- ALTERAR

// Senha secreta para evitar acesso não autorizado (opcional mas recomendado)
$SECRET_KEY = 'rodust_webhook_2024'; // <-- ALTERAR para algo único

// ============================================
// NÃO EDITAR ABAIXO DESTA LINHA
// ============================================

header('Content-Type: application/json');

// Log para debug (opcional - remover em produção)
$logFile = __DIR__ . '/webhook-proxy.log';
$logEnabled = true; // Mudar para false em produção

function logMessage($message) {
    global $logFile, $logEnabled;
    if ($logEnabled) {
        file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
    }
}

// Verificar se está configurado
if ($FORWARD_TO === 'http://SEU_IP_PUBLICO:8000/api/webhook/mercadopago') {
    logMessage('ERRO: Forward URL não configurada!');
    http_response_code(500);
    echo json_encode(['error' => 'Webhook proxy not configured']);
    exit;
}

// Verificar autenticação básica (opcional)
$authHeader = $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? '';
if ($SECRET_KEY && $authHeader !== $SECRET_KEY) {
    // Comentar estas 4 linhas se não quiser validação de secret
    // logMessage('ERRO: Secret inválido');
    // http_response_code(401);
    // echo json_encode(['error' => 'Unauthorized']);
    // exit;
}

try {
    // Capturar dados do webhook
    $method = $_SERVER['REQUEST_METHOD'];
    $headers = getallheaders();
    $body = file_get_contents('php://input');
    $queryString = $_SERVER['QUERY_STRING'] ?? '';
    
    logMessage("Recebido: $method " . ($queryString ? "?$queryString" : ""));
    logMessage("Body: " . substr($body, 0, 200)); // Primeiros 200 chars
    
    // Preparar requisição para localhost
    $ch = curl_init();
    
    $url = $FORWARD_TO;
    if ($queryString) {
        $url .= '?' . $queryString;
    }
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'User-Agent: MercadoPago-Webhook-Proxy/1.0',
            'X-Forwarded-For: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
            'X-Original-User-Agent: ' . ($headers['User-Agent'] ?? 'unknown'),
        ],
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);
    
    // Encaminhar para localhost
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        logMessage("ERRO CURL: $error");
        http_response_code(502);
        echo json_encode(['error' => 'Failed to forward to localhost', 'details' => $error]);
        exit;
    }
    
    logMessage("Encaminhado com sucesso. Status: $httpCode");
    
    // Retornar resposta do localhost para Mercado Pago
    http_response_code($httpCode);
    echo $response;
    
} catch (Exception $e) {
    logMessage("EXCEÇÃO: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
