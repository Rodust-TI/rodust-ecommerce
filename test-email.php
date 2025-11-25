<?php

use App\Models\Customer;
use App\Mail\CustomerVerificationMail;
use Illuminate\Support\Facades\Mail;

// Buscar último customer
$customer = Customer::orderBy('id', 'desc')->first();

if (!$customer) {
    echo "Nenhum customer encontrado. Crie um cadastro primeiro.\n";
    exit;
}

echo "Customer encontrado: {$customer->name} ({$customer->email})\n";
echo "Token: {$customer->verification_token}\n\n";

// Gerar URL de verificação
$verificationUrl = 'http://localhost:8080/verificar-email?token=' . $customer->verification_token;

echo "URL de verificação: {$verificationUrl}\n\n";

// Tentar enviar email
try {
    Mail::to($customer->email)->send(new CustomerVerificationMail($customer, $verificationUrl));
    echo "✓ Email enviado com sucesso para {$customer->email}!\n";
    echo "Verifique sua caixa de entrada e spam.\n";
} catch (\Exception $e) {
    echo "✗ ERRO ao enviar email:\n";
    echo $e->getMessage() . "\n";
    echo "\nDetalhes do erro:\n";
    echo $e->getTraceAsString() . "\n";
}
