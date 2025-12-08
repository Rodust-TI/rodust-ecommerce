<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Fiscal Emitida - Rodust</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #1a1a1a; color: #fff; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
        <h1 style="margin: 0; font-size: 24px;">📄 Nota Fiscal Emitida</h1>
    </div>
    
    <div style="background-color: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px;">
        <p>Olá <strong>{{ $customerName }}</strong>,</p>
        
        <p>A nota fiscal do seu pedido <strong>#{{ $orderNumber }}</strong> foi emitida com sucesso!</p>
        
        <div style="background-color: #fff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #9333ea;">
            <h2 style="margin-top: 0; color: #9333ea;">Informações da Nota Fiscal</h2>
            <p><strong>Número da NF:</strong> {{ $invoiceNumber }}</p>
            <p><strong>Chave de Acesso:</strong> {{ $invoiceKey }}</p>
            @if($issuedAt)
            <p><strong>Data de Emissão:</strong> {{ \Carbon\Carbon::parse($issuedAt)->format('d/m/Y H:i') }}</p>
            @endif
        </div>
        
        @if($invoicePdfUrl)
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $invoicePdfUrl }}" 
               style="display: inline-block; background-color: #9333ea; color: #fff; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold;">
                📥 Baixar PDF da Nota Fiscal
            </a>
        </div>
        @endif
        
        <p style="margin-top: 30px; color: #666; font-size: 14px;">
            Esta nota fiscal também está disponível na sua área de pedidos em nosso site.
        </p>
        
        <hr style="border: none; border-top: 1px solid #ddd; margin: 30px 0;">
        
        <p style="color: #666; font-size: 12px; margin: 0;">
            Se você tiver alguma dúvida, entre em contato conosco através do nosso suporte.
        </p>
    </div>
    
    <div style="text-align: center; margin-top: 20px; color: #999; font-size: 12px;">
        <p>Rodust.com.br - Todos os direitos reservados</p>
    </div>
</body>
</html>

