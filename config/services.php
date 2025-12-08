<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'bling' => [
        // API v3 (OAuth2)
        'client_id' => env('BLING_CLIENT_ID'),
        'client_secret' => env('BLING_CLIENT_SECRET'),
        'base_url' => env('BLING_BASE_URL', 'https://api.bling.com.br/Api/v3'),
        'default_warehouse_id' => env('BLING_DEFAULT_WAREHOUSE_ID', 1),
        'customer_type_id' => env('BLING_CUSTOMER_TYPE_ID'), // ID do tipo "Cliente ecommerce"
        
        // Redirect para OAuth (configure no painel Bling)
        'redirect_uri' => env('BLING_REDIRECT_URI', env('APP_URL') . '/bling/callback'),
        
        // Formas de pagamento (IDs do Bling)
        // Use o comando: php artisan bling:list-payment-methods
        'payment_methods' => [
            'pix' => env('BLING_PAYMENT_METHOD_PIX'),
            'credit_card' => env('BLING_PAYMENT_METHOD_CREDIT_CARD'),
            'debit_card' => env('BLING_PAYMENT_METHOD_DEBIT_CARD'),
            'boleto' => env('BLING_PAYMENT_METHOD_BOLETO'),
            'default' => env('BLING_PAYMENT_METHOD_DEFAULT', 6061520), // Padrão caso não encontre
        ],
        
        // Situações (Status) de pedidos (IDs do Bling)
        // Use o comando: php artisan bling:test-statuses para verificar IDs corretos
        // IDs padrão baseados na API do Bling (podem ser sobrescritos via .env)
        'order_statuses' => [
            'open' => env('BLING_ORDER_STATUS_OPEN', 6), // Em aberto
            'processing' => env('BLING_ORDER_STATUS_PROCESSING', 15), // Em andamento
            'shipped' => env('BLING_ORDER_STATUS_SHIPPED'), // Enviado (se existir)
            'completed' => env('BLING_ORDER_STATUS_COMPLETED'), // Concluído (se existir)
            'cancelled' => env('BLING_ORDER_STATUS_CANCELLED', 12), // Cancelado
            'attended' => env('BLING_ORDER_STATUS_ATTENDED', 9), // Atendido
        ],
    ],

    'melhor_envio' => [
        // Modo de operação
        'mode' => env('INTEGRATIONS_MODE', 'sandbox'),
        
        // Configurações gerais
        'origin_cep' => env('MELHOR_ENVIO_ORIGIN_CEP', '13400710'),
        
        // OAuth2 - Sandbox
        'client_id_sandbox' => env('MELHOR_ENVIO_CLIENT_ID_SANDBOX'),
        'client_secret_sandbox' => env('MELHOR_ENVIO_CLIENT_SECRET_SANDBOX'),
        
        // OAuth2 - Produção
        'client_id_prod' => env('MELHOR_ENVIO_CLIENT_ID_PROD'),
        'client_secret_prod' => env('MELHOR_ENVIO_CLIENT_SECRET_PROD'),
        
        // URLs
        'sandbox_url' => 'https://sandbox.melhorenvio.com.br/api/v2',
        'prod_url' => 'https://melhorenvio.com.br/api/v2',
    ],

    'wordpress' => [
        'url' => env('WORDPRESS_URL', 'https://rodust.com.br'),
        'sync_url' => env('WORDPRESS_SYNC_URL', env('WORDPRESS_URL', 'https://rodust.com.br')),
        'api_user' => env('WORDPRESS_API_USER'),
        'api_password' => env('WORDPRESS_API_PASSWORD'), // Application Password do WordPress
    ],

    'mercadopago' => [
        // Modo de operação
        'mode' => env('INTEGRATIONS_MODE', 'sandbox'),
        
        // Credenciais Sandbox
        'public_key_sandbox' => env('MERCADOPAGO_PUBLIC_KEY_SANDBOX'),
        'access_token_sandbox' => env('MERCADOPAGO_ACCESS_TOKEN_SANDBOX'),
        
        // Credenciais Produção
        'public_key_prod' => env('MERCADOPAGO_PUBLIC_KEY_PROD'),
        'access_token_prod' => env('MERCADOPAGO_ACCESS_TOKEN_PROD'),
        
        // Webhook
        'webhook_url' => env('MERCADOPAGO_WEBHOOK_URL'),
        'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URL'),
    ],

];
