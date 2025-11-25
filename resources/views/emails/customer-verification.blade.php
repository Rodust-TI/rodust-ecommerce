<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirme seu cadastro</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .header h1 {
            color: white;
            margin: 0;
            font-size: 24px;
        }
        .content {
            background: #f9f9f9;
            padding: 40px 30px;
            border-radius: 0 0 10px 10px;
        }
        .button {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 15px 40px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .button:hover {
            background: #764ba2;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #999;
            font-size: 12px;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎉 Bem-vindo à Rodust!</h1>
    </div>
    
    <div class="content">
        <h2>Olá, {{ $customerName }}!</h2>
        
        <p>Obrigado por se cadastrar em nossa loja. Estamos muito felizes em tê-lo(a) conosco!</p>
        
        <p>Para concluir seu cadastro e começar a comprar, por favor confirme seu endereço de e-mail clicando no botão abaixo:</p>
        
        <center>
            <a href="{{ $verificationUrl }}" class="button">
                ✓ Confirmar E-mail
            </a>
        </center>
        
        <div class="warning">
            <strong>⏰ Atenção:</strong> Este link é válido por 24 horas.
        </div>
        
        <p style="margin-top: 30px; font-size: 14px; color: #666;">
            Se você não conseguir clicar no botão, copie e cole o link abaixo no seu navegador:
        </p>
        <p style="font-size: 12px; word-break: break-all; color: #999;">
            {{ $verificationUrl }}
        </p>
        
        <hr style="margin: 30px 0; border: none; border-top: 1px solid #ddd;">
        
        <p style="font-size: 14px; color: #666;">
            Se você não se cadastrou em nossa loja, por favor ignore este e-mail.
        </p>
    </div>
    
    <div class="footer">
        <p>© {{ date('Y') }} Rodust - Todos os direitos reservados</p>
        <p>noreply@rodust.com.br</p>
    </div>
</body>
</html>
