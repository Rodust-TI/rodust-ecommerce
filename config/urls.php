<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application URLs Configuration
    |--------------------------------------------------------------------------
    |
    | Centraliza todas as URLs da aplicação para facilitar manutenção
    | e migração entre ambientes (local/staging/production).
    |
    | Para alterar entre HTTP/HTTPS, basta modificar APP_PROTOCOL no .env
    | Para alterar domínio, basta modificar DOMAIN no .env
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Protocolo e Domínio Base
    |--------------------------------------------------------------------------
    */
    'protocol' => env('APP_PROTOCOL', 'http'),
    'domain' => env('DOMAIN', 'localhost'),
    'environment' => env('ENVIRONMENT', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Laravel (API Backend)
    |--------------------------------------------------------------------------
    */
    'laravel' => [
        // URL externa (usada pelo navegador/internet)
        'external' => env('LARAVEL_EXTERNAL_URL', env('APP_PROTOCOL', 'http') . '://' . env('DOMAIN', 'localhost') . ':8000'),
        
        // URL interna (usada dentro da rede Docker)
        'internal' => env('LARAVEL_INTERNAL_URL', 'http://laravel.test'),
        
        // Porta
        'port' => env('APP_PORT', 8000),
        
        // Atalhos úteis
        'base_url' => env('LARAVEL_EXTERNAL_URL', env('APP_PROTOCOL', 'http') . '://' . env('DOMAIN', 'localhost') . ':8000'),
        'api_url' => env('API_BASE_URL', env('LARAVEL_EXTERNAL_URL', env('APP_PROTOCOL', 'http') . '://' . env('DOMAIN', 'localhost') . ':8000') . '/api'),
        'bling_url' => env('LARAVEL_EXTERNAL_URL', env('APP_PROTOCOL', 'http') . '://' . env('DOMAIN', 'localhost') . ':8000') . '/bling',
    ],

    /*
    |--------------------------------------------------------------------------
    | WordPress (Frontend)
    |--------------------------------------------------------------------------
    */
    'wordpress' => [
        // URL externa (usada pelo navegador/internet)
        'external' => env('WORDPRESS_EXTERNAL_URL', 'https://' . env('DOMAIN', 'localhost') . ':8443'),
        
        // URL interna (usada dentro da rede Docker)
        'internal' => env('WORDPRESS_INTERNAL_URL', 'http://wordpress'),
        
        // Portas
        'port' => env('WP_PORT', 8080),
        'ssl_port' => env('WP_SSL_PORT', 8443),
        
        // Atalhos úteis
        'base_url' => env('WORDPRESS_EXTERNAL_URL', 'https://' . env('DOMAIN', 'localhost') . ':8443'),
        'verify_email' => env('WORDPRESS_EXTERNAL_URL', 'https://' . env('DOMAIN', 'localhost') . ':8443') . '/verificar-email',
    ],

    /*
    |--------------------------------------------------------------------------
    | CORS (Cross-Origin Resource Sharing)
    |--------------------------------------------------------------------------
    | Lista de origens permitidas para requisições cross-origin
    */
    'cors' => [
        'allowed_origins' => [
            // Laravel (API)
            env('APP_PROTOCOL', 'http') . '://' . env('DOMAIN', 'localhost') . ':' . env('APP_PORT', 8000),
            'http://' . env('DOMAIN', 'localhost') . ':' . env('APP_PORT', 8000),
            'https://' . env('DOMAIN', 'localhost') . ':' . env('APP_PORT', 8000),
            
            // WordPress
            env('WORDPRESS_EXTERNAL_URL', 'https://' . env('DOMAIN', 'localhost') . ':8443'),
            'http://' . env('DOMAIN', 'localhost') . ':' . env('WP_PORT', 8080),
            'https://' . env('DOMAIN', 'localhost') . ':' . env('WP_SSL_PORT', 8443),
            
            // Localhost genérico
            'http://localhost',
            'https://localhost',
            'http://127.0.0.1',
            'https://127.0.0.1',
            
            // Frontend alternativo (se houver)
            'http://' . env('DOMAIN', 'localhost') . ':3000',
            'https://' . env('DOMAIN', 'localhost') . ':3000',
        ],
        
        'default_origin' => env('LARAVEL_EXTERNAL_URL', 'http://localhost:8000'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Integrations (Webhooks, Callbacks)
    |--------------------------------------------------------------------------
    */
    'integrations' => [
        'mercadopago' => [
            'webhook_url' => env('MERCADOPAGO_WEBHOOK_URL', null), // null = não configurado (localhost não aceito)
            'callback_url' => env('WORDPRESS_EXTERNAL_URL', 'https://localhost:8443') . '/pagamento-confirmado',
        ],
        
        'bling' => [
            'callback_url' => env('BLING_CALLBACK_URL', env('LARAVEL_EXTERNAL_URL', 'http://localhost:8000') . '/bling/callback'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    | Funções auxiliares podem acessar este config via config('urls.laravel.api_url')
    */

];
