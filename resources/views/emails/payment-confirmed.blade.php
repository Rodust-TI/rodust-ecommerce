<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento Confirmado</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border: 1px solid #ddd;
            border-radius: 0 0 5px 5px;
        }
        .order-details {
            background-color: white;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid #4CAF50;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 12px;
        }
        .success-icon {
            font-size: 48px;
            color: #4CAF50;
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>✓ Pagamento Confirmado!</h1>
    </div>
    
    <div class="content">
        <p>Olá, <strong>{{ $customerName }}</strong>!</p>
        
        <div class="success-icon">✓</div>
        
        <p>Temos uma ótima notícia! Seu pagamento foi confirmado com sucesso.</p>
        
        <div class="order-details">
            <h3 style="margin-top: 0;">Detalhes do Pedido</h3>
            <p><strong>Número do Pedido:</strong> #{{ $orderNumber }}</p>
            <p><strong>Valor Total:</strong> R$ {{ number_format($orderTotal, 2, ',', '.') }}</p>
            <p><strong>Forma de Pagamento:</strong> {{ ucfirst($paymentMethod) }}</p>
            <p><strong>Data de Pagamento:</strong> {{ $paidAt->format('d/m/Y \à\s H:i') }}</p>
        </div>
        
        <p>Seu pedido já está em processamento e em breve será enviado para separação.</p>
        
        <p>Você receberá uma nova notificação quando seu pedido for despachado, com o código de rastreamento.</p>
        
        <p>Se tiver alguma dúvida, entre em contato conosco através do email: <a href="mailto:contato@rodust.com.br">contato@rodust.com.br</a></p>
        
        <p style="margin-top: 30px;">Obrigado por comprar conosco!</p>
        
        <p><strong>Equipe Rodust</strong></p>
    </div>
    
    <div class="footer">
        <p>Este é um e-mail automático, por favor não responda.</p>
        <p>&copy; {{ date('Y') }} Rodust. Todos os direitos reservados.</p>
    </div>
</body>
</html>
