<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperação de Senha</title>
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
            color: #2563eb;
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
            background-color: #2563eb;
            color: white !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #1d4ed8;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #666;
            margin-top: 20px;
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
            <h1>🔐 Recuperação de Senha</h1>
        </div>
        
        <div class="content">
            <p>Olá, <strong>{{ $customer->name }}</strong>!</p>
            
            <p>Recebemos uma solicitação para redefinir a senha da sua conta em <strong>Rodust</strong>.</p>
            
            <p>Clique no botão abaixo para criar uma nova senha:</p>
            
            <div style="text-align: center;">
                <a href="{{ $resetUrl }}" class="button">Redefinir Senha</a>
            </div>
            
            <div class="warning">
                ⏱️ <strong>Este link expira em 1 hora</strong> por questões de segurança.
            </div>
            
            <p style="margin-top: 20px; font-size: 14px; color: #666;">
                Se o botão não funcionar, copie e cole o link abaixo no seu navegador:
            </p>
            <p class="link" style="font-size: 12px;">{{ $resetUrl }}</p>
            
            <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 25px 0;">
            
            <p style="font-size: 13px; color: #666;">
                <strong>Não solicitou esta recuperação?</strong><br>
                Ignore este email. Sua senha não será alterada.
            </p>
        </div>
        
        <div class="footer">
            <p>Este é um email automático. Por favor, não responda.</p>
            <p>&copy; {{ date('Y') }} Rodust. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>
