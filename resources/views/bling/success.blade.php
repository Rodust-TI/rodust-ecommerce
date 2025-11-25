<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bling - Autenticação Concluída</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 48px;
            max-width: 600px;
            width: 100%;
            text-align: center;
        }
        .icon {
            width: 80px;
            height: 80px;
            background: #10b981;
            border-radius: 50%;
            margin: 0 auto 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
        }
        h1 {
            color: #1f2937;
            font-size: 28px;
            margin-bottom: 12px;
        }
        p {
            color: #6b7280;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 32px;
        }
        .info {
            background: #f3f4f6;
            border-radius: 8px;
            padding: 24px;
            text-align: left;
            margin-bottom: 32px;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .info-label {
            color: #6b7280;
            font-weight: 500;
        }
        .info-value {
            color: #1f2937;
            font-weight: 600;
            font-family: 'Courier New', monospace;
        }
        .success {
            color: #10b981;
        }
        .btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 14px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .note {
            margin-top: 24px;
            font-size: 14px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">✓</div>
        
        <h1>Autenticação Concluída!</h1>
        <p>A integração com o Bling foi autorizada com sucesso. Os tokens foram salvos automaticamente.</p>
        
        <div class="info">
            <div class="info-item">
                <span class="info-label">Status:</span>
                <span class="info-value success">Conectado</span>
            </div>
            <div class="info-item">
                <span class="info-label">Access Token:</span>
                <span class="info-value">{{ $access_token }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Expira em:</span>
                <span class="info-value">{{ $expires_hours }}h ({{ $expires_in }}s)</span>
            </div>
            <div class="info-item">
                <span class="info-label">Refresh Token:</span>
                <span class="info-value success">Salvo (válido por 30 dias)</span>
            </div>
        </div>

        <p class="note">
            <strong>💡 Próximos passos:</strong><br>
            Execute <code>php artisan bling:list-products</code> no terminal<br>
            para testar a conexão e importar produtos.
        </p>

        <a href="javascript:window.close()" class="btn">Fechar Janela</a>
    </div>

    <script>
        // Auto-fechar após 10 segundos
        setTimeout(() => {
            window.close();
        }, 10000);
    </script>
</body>
</html>
