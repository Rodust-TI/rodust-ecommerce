<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\ERP\BlingV3Adapter;

echo "Tentando renovar token do Bling...\n";

$adapter = new BlingV3Adapter();

try {
    $result = $adapter->getProducts(['limite' => 1]);
    echo "✅ Token renovado com sucesso!\n";
    echo "Produtos retornados: " . count($result) . "\n";
} catch (\Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
