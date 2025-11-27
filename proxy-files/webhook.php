<?php
/**
 * Proxy Webhook - Melhor Envio
 * 
 * Este arquivo recebe webhooks do Melhor Envio e encaminha para seu localhost
 * 
 * INSTRUÇÕES:
 * 1. Fazer upload para: https://rodust.com.br/melhor-envio/webhook.php
 * 2. Configurar no Melhor Envio a URL: https://rodust.com.br/melhor-envio/webhook.php
 */

// Configuração - ALTERE AQUI O IP/PORTA DO SEU LOCALHOST
define('LOCALHOST_URL', 'http://localhost:8000');

// Pegar dados do webhook
$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true);

// Log para debug
$logFile = __DIR__ . '/webhook.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Webhook recebido\n", FILE_APPEND);
file_put_contents($logFile, "Headers: " . json_encode(getallheaders()) . "\n", FILE_APPEND);
file_put_contents($logFile, "Body: {$rawBody}\n", FILE_APPEND);
file_put_contents($logFile, str_repeat('-', 80) . "\n", FILE_APPEND);

// Encaminhar para localhost usando cURL
$ch = curl_init(LOCALHOST_URL . '/api/melhor-envio/webhook');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $rawBody,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-Forwarded-From: rodust.com.br',
    ],
    CURLOPT_TIMEOUT => 30,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Log da resposta
file_put_contents($logFile, "Response Code: {$httpCode}\n", FILE_APPEND);
file_put_contents($logFile, "Response Body: {$response}\n", FILE_APPEND);
if ($error) {
    file_put_contents($logFile, "Error: {$error}\n", FILE_APPEND);
}
file_put_contents($logFile, str_repeat('=', 80) . "\n\n", FILE_APPEND);

// Responder ao Melhor Envio
http_response_code($httpCode ?: 200);
header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Webhook recebido e encaminhado']);
