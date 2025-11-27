<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\ERP\BlingV3Adapter;

echo "=== Buscando Formas de Pagamento no Bling ===\n\n";

$bling = new BlingV3Adapter();

try {
    $formas = $bling->getPaymentMethods();
    
    if (!empty($formas)) {
        echo "✅ Formas de pagamento encontradas:\n\n";
        
        foreach ($formas as $forma) {
            echo "ID: {$forma['id']} - {$forma['descricao']}\n";
            if (isset($forma['tipoPagamento'])) {
                echo "  Tipo: {$forma['tipoPagamento']}\n";
            }
            if (isset($forma['situacao'])) {
                echo "  Situação: {$forma['situacao']}\n";
            }
            echo "\n";
        }
    } else {
        echo "❌ Nenhuma forma de pagamento encontrada\n";
    }
} catch (\Exception $e) {
    echo "❌ ERRO: {$e->getMessage()}\n";
}

echo "\n=== Fim ===\n";
