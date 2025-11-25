<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

$token = Cache::get('bling_access_token');

if (!$token) {
    die("Token not found. Please authenticate first.\n");
}

// Teste 1: Física (com acento)
$payload1 = [
    'nome' => 'Teste API Fisica',
    'tipo' => 'Física',
    'email' => 'teste1@example.com',
    'numeroDocumento' => '12345678901'
];

echo "Teste 1: tipo = 'Física' (com acento)\n";
$response1 = Http::withToken($token)
    ->post('https://www.bling.com.br/Api/v3/contatos', $payload1);

echo "Status: " . $response1->status() . "\n";
echo "Response: " . $response1->body() . "\n\n";

// Teste 2: Fisica (sem acento)
$payload2 = [
    'nome' => 'Teste API Fisica',
    'tipo' => 'Fisica',
    'email' => 'teste2@example.com',
    'numeroDocumento' => '12345678902'
];

echo "Teste 2: tipo = 'Fisica' (sem acento)\n";
$response2 = Http::withToken($token)
    ->post('https://www.bling.com.br/Api/v3/contatos', $payload2);

echo "Status: " . $response2->status() . "\n";
echo "Response: " . $response2->body() . "\n\n";

// Teste 3: F (abreviado) com campos obrigatórios
$payload3 = [
    'nome' => 'Teste API F',
    'tipo' => 'F',
    'situacao' => 'A',
    'email' => 'teste3@example.com',
    'numeroDocumento' => '35246710807', // CPF válido
    'indicadorIe' => 9,
    'contribuinte' => 9
];

echo "Teste 3: tipo = 'F' (abreviado) com situacao\n";
$response3 = Http::withToken($token)
    ->post('https://www.bling.com.br/Api/v3/contatos', $payload3);

echo "Status: " . $response3->status() . "\n";
echo "Response: " . $response3->body() . "\n";
