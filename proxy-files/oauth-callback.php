<?php
/**
 * Proxy OAuth Callback - Melhor Envio
 * 
 * Este arquivo recebe o callback do Melhor Envio e redireciona para seu localhost
 * 
 * INSTRUÇÕES:
 * 1. Fazer upload para: https://rodust.com.br/melhor-envio/oauth-callback.php
 * 2. Configurar no Melhor Envio a URL: https://rodust.com.br/melhor-envio/oauth-callback.php
 */

// Configuração - ALTERE AQUI O IP/PORTA DO SEU LOCALHOST
define('LOCALHOST_URL', 'http://localhost:8000');

// Pegar parâmetros do callback OAuth
$code = $_GET['code'] ?? null;
$state = $_GET['state'] ?? null;
$error = $_GET['error'] ?? null;

// Log para debug (opcional)
$logFile = __DIR__ . '/oauth-callback.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Callback recebido\n", FILE_APPEND);
file_put_contents($logFile, "Code: {$code}\n", FILE_APPEND);
file_put_contents($logFile, "State: {$state}\n", FILE_APPEND);
file_put_contents($logFile, "Error: {$error}\n", FILE_APPEND);

// Se tem erro, redirecionar com erro
if ($error) {
    header('Location: ' . LOCALHOST_URL . '/api/melhor-envio/oauth/callback?error=' . urlencode($error));
    exit;
}

// Se não tem code, erro
if (!$code) {
    header('Location: ' . LOCALHOST_URL . '/api/melhor-envio/oauth/callback?error=no_code');
    exit;
}

// Redirecionar para localhost com os parâmetros
$params = http_build_query([
    'code' => $code,
    'state' => $state,
]);

header('Location: ' . LOCALHOST_URL . '/api/melhor-envio/oauth/callback?' . $params);
exit;
