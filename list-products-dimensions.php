<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$products = App\Models\Product::all();

echo "Produtos no Laravel (com dimensões):\n";
echo str_repeat('-', 100) . "\n";

foreach ($products as $p) {
    echo sprintf(
        "ID: %d | Nome: %s\n",
        $p->id,
        $p->name
    );
    echo sprintf(
        "  Peso: %.3f kg | Largura: %.2f cm | Altura: %.2f cm | Comprimento: %.2f cm\n",
        $p->weight ?? 0,
        $p->width ?? 0,
        $p->height ?? 0,
        $p->length ?? 0
    );
    echo sprintf(
        "  Estoque: %d | Preço: R$ %.2f | SKU: %s | Bling ID: %s\n\n",
        $p->stock,
        $p->price,
        $p->sku,
        $p->bling_id
    );
}

echo str_repeat('-', 100) . "\n";
