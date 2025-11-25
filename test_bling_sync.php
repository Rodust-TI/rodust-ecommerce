<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$customer = App\Models\Customer::first();
$service = new App\Services\BlingCustomerService();

// Usar reflection para acessar método privado
$reflection = new ReflectionClass($service);
$method = $reflection->getMethod('prepareCustomerPayload');
$method->setAccessible(true);

$payload = $method->invoke($service, $customer);

echo "Payload gerado:\n";
echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\nTipo enviado: " . $payload['tipo'] . "\n";
