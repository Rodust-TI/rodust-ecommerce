# Integração Laravel + WordPress

Este documento descreve como integrar o backend Laravel (API REST) com o frontend WordPress.

## Arquitetura

```
WordPress (Frontend)
    ↓ Consome API REST
Laravel (Backend + API)
    ↓ Sincroniza
Bling ERP (Sistema de Origem)
```

## 1. Configuração do WordPress

### 1.1 Instalar WordPress

Instale o WordPress em um diretório separado ou subdomínio:
- **Backend Laravel**: `http://localhost` ou `https://api.rodust.com.br`
- **Frontend WordPress**: `http://localhost:8080` ou `https://rodust.com.br`

### 1.2 Criar Plugin Customizado

Crie um plugin para consumir a API Laravel:

**wp-content/plugins/rodust-ecommerce/rodust-ecommerce.php:**

```php
<?php
/**
 * Plugin Name: Rodust Ecommerce
 * Description: Integração com API Laravel
 * Version: 1.0
 * Author: Rodust
 */

defined('ABSPATH') || exit;

// Configurações da API
define('RODUST_API_URL', 'http://localhost/api'); // URL do Laravel
define('RODUST_API_TIMEOUT', 30);

// Incluir arquivos do plugin
require_once plugin_dir_path(__FILE__) . 'includes/api-client.php';
require_once plugin_dir_path(__FILE__) . 'includes/product-functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/checkout-functions.php';
```

### 1.3 Cliente da API

**wp-content/plugins/rodust-ecommerce/includes/api-client.php:**

```php
<?php

class Rodust_API_Client {
    
    private $api_url;
    
    public function __construct() {
        $this->api_url = RODUST_API_URL;
    }
    
    /**
     * Fazer requisição GET
     */
    public function get($endpoint, $params = []) {
        $url = $this->api_url . $endpoint;
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $response = wp_remote_get($url, [
            'timeout' => RODUST_API_TIMEOUT,
            'headers' => [
                'Accept' => 'application/json',
            ]
        ]);
        
        return $this->handle_response($response);
    }
    
    /**
     * Fazer requisição POST
     */
    public function post($endpoint, $data = []) {
        $url = $this->api_url . $endpoint;
        
        $response = wp_remote_post($url, [
            'timeout' => RODUST_API_TIMEOUT,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'body' => json_encode($data),
        ]);
        
        return $this->handle_response($response);
    }
    
    /**
     * Processar resposta da API
     */
    private function handle_response($response) {
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error' => $response->get_error_message()
            ];
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        $status = wp_remote_retrieve_response_code($response);
        
        return [
            'success' => $status >= 200 && $status < 300,
            'data' => $data,
            'status' => $status,
        ];
    }
}
```

### 1.4 Funções de Produtos

**wp-content/plugins/rodust-ecommerce/includes/product-functions.php:**

```php
<?php

/**
 * Buscar produtos da API
 */
function rodust_get_products($params = []) {
    $api = new Rodust_API_Client();
    return $api->get('/products', $params);
}

/**
 * Buscar um produto específico
 */
function rodust_get_product($id) {
    $api = new Rodust_API_Client();
    return $api->get("/products/{$id}");
}

/**
 * Shortcode para listar produtos
 */
function rodust_products_shortcode($atts) {
    $atts = shortcode_atts([
        'per_page' => 12,
        'search' => '',
    ], $atts);
    
    $result = rodust_get_products($atts);
    
    if (!$result['success']) {
        return '<p>Erro ao carregar produtos.</p>';
    }
    
    $products = $result['data']['data'] ?? [];
    
    ob_start();
    ?>
    <div class="rodust-products-grid">
        <?php foreach ($products as $product): ?>
            <div class="rodust-product-card">
                <?php if ($product['image']): ?>
                    <img src="<?php echo esc_url($product['image']); ?>" alt="<?php echo esc_attr($product['name']); ?>">
                <?php endif; ?>
                <h3><?php echo esc_html($product['name']); ?></h3>
                <p class="price">R$ <?php echo number_format($product['price'], 2, ',', '.'); ?></p>
                <button class="add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                    Adicionar ao Carrinho
                </button>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('rodust_products', 'rodust_products_shortcode');
```

### 1.5 Funções de Checkout

**wp-content/plugins/rodust-ecommerce/includes/checkout-functions.php:**

```php
<?php

/**
 * Processar checkout
 */
function rodust_process_checkout($order_data) {
    $api = new Rodust_API_Client();
    return $api->post('/orders', $order_data);
}

/**
 * AJAX para adicionar ao carrinho
 */
function rodust_add_to_cart_ajax() {
    check_ajax_referer('rodust_cart_nonce', 'nonce');
    
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity'] ?? 1);
    
    // Buscar produto da API
    $result = rodust_get_product($product_id);
    
    if (!$result['success']) {
        wp_send_json_error(['message' => 'Produto não encontrado']);
    }
    
    $product = $result['data'];
    
    // Adicionar ao carrinho (sessão)
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    $cart_key = $product_id;
    
    if (isset($_SESSION['cart'][$cart_key])) {
        $_SESSION['cart'][$cart_key]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$cart_key] = [
            'product_id' => $product_id,
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity,
            'sku' => $product['sku'],
        ];
    }
    
    wp_send_json_success([
        'message' => 'Produto adicionado ao carrinho',
        'cart_count' => count($_SESSION['cart']),
    ]);
}
add_action('wp_ajax_rodust_add_to_cart', 'rodust_add_to_cart_ajax');
add_action('wp_ajax_nopriv_rodust_add_to_cart', 'rodust_add_to_cart_ajax');

/**
 * Processar finalização de compra
 */
function rodust_finalize_checkout_ajax() {
    check_ajax_referer('rodust_checkout_nonce', 'nonce');
    
    $customer = [
        'name' => sanitize_text_field($_POST['customer_name']),
        'email' => sanitize_email($_POST['customer_email']),
        'phone' => sanitize_text_field($_POST['customer_phone'] ?? ''),
        'cpf_cnpj' => sanitize_text_field($_POST['customer_cpf'] ?? ''),
    ];
    
    $cart = $_SESSION['cart'] ?? [];
    
    if (empty($cart)) {
        wp_send_json_error(['message' => 'Carrinho vazio']);
    }
    
    $items = array_values($cart);
    
    $order_data = [
        'customer' => $customer,
        'items' => $items,
        'shipping' => floatval($_POST['shipping'] ?? 0),
        'discount' => floatval($_POST['discount'] ?? 0),
        'payment_method' => sanitize_text_field($_POST['payment_method'] ?? ''),
    ];
    
    $result = rodust_process_checkout($order_data);
    
    if ($result['success']) {
        // Limpar carrinho
        unset($_SESSION['cart']);
        
        wp_send_json_success([
            'message' => 'Pedido criado com sucesso!',
            'order' => $result['data'],
        ]);
    } else {
        wp_send_json_error([
            'message' => 'Erro ao processar pedido',
            'error' => $result['data']['error'] ?? 'Erro desconhecido',
        ]);
    }
}
add_action('wp_ajax_rodust_checkout', 'rodust_finalize_checkout_ajax');
add_action('wp_ajax_nopriv_rodust_checkout', 'rodust_finalize_checkout_ajax');
```

## 2. Uso no WordPress

### 2.1 Listar Produtos em uma Página

Crie uma página no WordPress e adicione o shortcode:

```
[rodust_products per_page="12"]
```

### 2.2 Adicionar JavaScript no Tema

Adicione no `functions.php` do tema:

```php
function rodust_enqueue_scripts() {
    wp_enqueue_script('rodust-cart', get_template_directory_uri() . '/js/cart.js', ['jquery'], '1.0', true);
    
    wp_localize_script('rodust-cart', 'rodust_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'cart_nonce' => wp_create_nonce('rodust_cart_nonce'),
        'checkout_nonce' => wp_create_nonce('rodust_checkout_nonce'),
    ]);
}
add_action('wp_enqueue_scripts', 'rodust_enqueue_scripts');
```

**js/cart.js:**

```javascript
jQuery(document).ready(function($) {
    // Adicionar ao carrinho
    $('.add-to-cart').on('click', function() {
        const productId = $(this).data('product-id');
        
        $.ajax({
            url: rodust_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'rodust_add_to_cart',
                nonce: rodust_ajax.cart_nonce,
                product_id: productId,
                quantity: 1,
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    // Atualizar contador do carrinho
                    $('.cart-count').text(response.data.cart_count);
                }
            },
            error: function() {
                alert('Erro ao adicionar produto ao carrinho');
            }
        });
    });
    
    // Finalizar compra
    $('#checkout-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        
        $.ajax({
            url: rodust_ajax.ajax_url,
            method: 'POST',
            data: formData + '&action=rodust_checkout&nonce=' + rodust_ajax.checkout_nonce,
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    window.location.href = '/pedido-confirmado';
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('Erro ao processar pedido');
            }
        });
    });
});
```

## 3. Endpoints da API Laravel

### Públicos (sem autenticação):

- `GET /api/products` - Listar produtos
- `GET /api/products/{id}` - Ver produto
- `POST /api/orders` - Criar pedido (checkout)
- `GET /api/orders/{id}` - Ver pedido

### Protegidos (requer token Sanctum):

- `POST /api/admin/products` - Criar produto
- `PUT /api/admin/products/{id}` - Atualizar produto
- `DELETE /api/admin/products/{id}` - Deletar produto
- `GET /api/admin/orders` - Listar pedidos
- `PUT /api/admin/orders/{id}` - Atualizar status do pedido

## 4. Considerações de Segurança

1. **CORS**: Configure o Laravel para aceitar requisições do domínio WordPress
2. **Rate Limiting**: Use throttle nas rotas públicas
3. **Validação**: Sempre valide os dados recebidos
4. **HTTPS**: Use SSL em produção
5. **Sanitização**: Sanitize todas as entradas no WordPress

## 5. Melhorias Futuras

- Cache de produtos no WordPress (Transients API)
- Webhooks do Bling para atualizar produtos automaticamente
- Sistema de cupons de desconto
- Cálculo de frete integrado com Correios
- Gateway de pagamento (Mercado Pago, PagSeguro)
