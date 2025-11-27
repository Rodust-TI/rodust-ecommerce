<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$products = App\Models\Product::all();

echo "Produtos no Laravel:\n";
echo str_repeat('-', 80) . "\n";

foreach ($products as $p) {
    echo sprintf(
        "ID: %d | SKU: %s | Nome: %s | Estoque: %d | Preço: %.2f | Bling ID: %s\n",
        $p->id,
        $p->sku,
        $p->name,
        $p->stock,
        $p->price,
        $p->bling_id
    );
}

echo str_repeat('-', 80) . "\n";
echo "Total: " . $products->count() . " produtos\n";
