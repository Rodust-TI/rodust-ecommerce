<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sua Conta foi Recuperada</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 30px;
            border: 1px solid #e0e0e0;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #10b981;
            margin: 0;
        }
        .content {
            background-color: white;
            padding: 25px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .button {
            display: inline-block;
            padding: 14px 30px;
            background-color: #10b981;
            color: white !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #059669;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #666;
            margin-top: 20px;
        }
        .info-box {
            background-color: #dbeafe;
            border-left: 4px solid #2563eb;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .warning {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 12px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .link {
            color: #2563eb;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Bem-vindo de Volta!</h1>
        </div>
        
        <div class="content">
            <p>Olá, <strong>{{ $customer->name }}</strong>!</p>
            
            <p>Temos boas notícias! Sua conta em <strong>Rodust</strong> foi recuperada com sucesso.</p>
            
            <div class="info-box">
                📧 <strong>Email:</strong> {{ $customer->email }}<br>
                @if($customer->cpf)
                🆔 <strong>CPF:</strong> {{ $customer->cpf }}<br>
                @endif
                📱 <strong>Telefone:</strong> {{ $customer->phone ?? 'Não cadastrado' }}
            </div>
            
            <p>Para garantir a segurança da sua conta, você precisa <strong>criar uma nova senha</strong>.</p>
            
            <div style="text-align: center;">
                <a href="{{ $resetUrl }}" class="button">Criar Minha Senha</a>
            </div>
            
            <div class="warning">
                ⏱️ <strong>Este link é válido por 7 dias.</strong> Após este período, você precisará solicitar um novo link.
            </div>
            
            <p style="margin-top: 20px; font-size: 14px; color: #666;">
                Se o botão não funcionar, copie e cole o link abaixo no seu navegador:
            </p>
            <p class="link" style="font-size: 12px;">{{ $resetUrl }}</p>
            
            <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 25px 0;">
            
            <h3 style="color: #2563eb; margin-top: 25px;">Após criar sua senha, você poderá:</h3>
            <ul style="line-height: 1.8;">
                <li>✅ Acessar todo o histórico de pedidos</li>
                <li>✅ Atualizar seus dados cadastrais</li>
                <li>✅ Fazer novos pedidos</li>
                <li>✅ Acompanhar entregas em tempo real</li>
            </ul>
            
            <p style="margin-top: 20px; font-size: 13px; color: #666;">
                <strong>Não reconhece esta conta?</strong><br>
                Entre em contato conosco imediatamente respondendo este email.
            </p>
        </div>
        
        <div class="footer">
            <p>Este é um email automático. Por favor, não responda.</p>
            <p>&copy; {{ date('Y') }} Rodust. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>
