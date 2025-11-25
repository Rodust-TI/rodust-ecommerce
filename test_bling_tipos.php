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

echo "Consultando tipos de contato no Bling...\n\n";

$response = Http::withToken($token)
    ->get('https://www.bling.com.br/Api/v3/contatos/tipos');

if ($response->successful()) {
    $data = $response->json();
    
    echo "Status: " . $response->status() . "\n";
    echo "Total de tipos: " . count($data['data'] ?? []) . "\n\n";
    
    echo "Tipos disponíveis:\n";
    echo str_repeat('-', 60) . "\n";
    
    foreach ($data['data'] ?? [] as $tipo) {
        echo sprintf("ID: %-12s | Descrição: %s\n", 
            $tipo['id'], 
            $tipo['descricao']
        );
    }
    
    echo str_repeat('-', 60) . "\n\n";
    
    // Procurar especificamente por "Cliente ecommerce"
    $clienteEcommerce = collect($data['data'] ?? [])
        ->firstWhere('descricao', 'Cliente ecommerce');
    
    if ($clienteEcommerce) {
        echo "✓ Tipo 'Cliente ecommerce' encontrado!\n";
        echo "  ID: " . $clienteEcommerce['id'] . "\n";
    } else {
        echo "✗ Tipo 'Cliente ecommerce' NÃO encontrado.\n";
        echo "  Você precisa criar este tipo no painel do Bling primeiro.\n";
    }
    
} else {
    echo "Erro ao consultar tipos:\n";
    echo "Status: " . $response->status() . "\n";
    echo "Response: " . $response->body() . "\n";
}
