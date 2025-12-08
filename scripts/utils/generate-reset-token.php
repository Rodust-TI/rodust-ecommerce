<?php

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$customer = \App\Models\Customer::where('email', 'sanozukez@gmail.com')->first();

if (!$customer) {
    die("Cliente não encontrado\n");
}

$token = \Illuminate\Support\Str::random(64);
$customer->password_reset_token = $token;
$customer->password_reset_token_expires_at = now()->addHour();
$customer->save();

echo "Token gerado: {$token}\n";
echo "Expira em: {$customer->password_reset_token_expires_at}\n";
echo "\nURL para teste:\n";
echo "https://localhost:8443/redefinir-senha/?token={$token}\n";
