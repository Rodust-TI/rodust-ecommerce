<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de Rastreio - Rodust</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #1a1a1a; color: #fff; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
        <h1 style="margin: 0; font-size: 24px;">🚚 Código de Rastreio Disponível</h1>
    </div>
    
    <div style="background-color: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px;">
        <p>Olá <strong>{{ $customerName }}</strong>,</p>
        
        <p>Seu pedido <strong>#{{ $orderNumber }}</strong> foi postado e já está a caminho!</p>
        
        <div style="background-color: #fff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #3b82f6;">
            <h2 style="margin-top: 0; color: #3b82f6;">Informações de Envio</h2>
            <p><strong>Código de Rastreio:</strong> <code style="background-color: #f3f4f6; padding: 4px 8px; border-radius: 4px; font-size: 16px; font-weight: bold;">{{ $trackingCode }}</code></p>
            @if($carrier)
            <p><strong>Transportadora:</strong> {{ $carrier }}</p>
            @endif
            @if($serviceName)
            <p><strong>Serviço:</strong> {{ $serviceName }}</p>
            @endif
            @if($shippedAt)
            <p><strong>Data de Postagem:</strong> {{ \Carbon\Carbon::parse($shippedAt)->format('d/m/Y H:i') }}</p>
            @endif
        </div>
        
        @if($trackingUrl)
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $trackingUrl }}" 
               target="_blank"
               style="display: inline-block; background-color: #3b82f6; color: #fff; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold;">
                🔍 Rastrear Pedido
            </a>
        </div>
        @endif
        
        <p style="margin-top: 30px; color: #666; font-size: 14px;">
            Você pode acompanhar o status da entrega usando o código de rastreio acima.
        </p>
        
        <hr style="border: none; border-top: 1px solid #ddd; margin: 30px 0;">
        
        <p style="color: #666; font-size: 12px; margin: 0;">
            Se você tiver alguma dúvida sobre a entrega, entre em contato conosco através do nosso suporte.
        </p>
    </div>
    
    <div style="text-align: center; margin-top: 20px; color: #999; font-size: 12px;">
        <p>Rodust.com.br - Todos os direitos reservados</p>
    </div>
</body>
</html>

