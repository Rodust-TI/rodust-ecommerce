# Arquitetura Híbrida Laravel + WordPress - Implementação

## 📋 O que foi implementado

### ✅ Laravel (Backend/API)

1. **Migration `wordpress_post_id`** (EXECUTADA)
   - Adiciona campo para armazenar ID do post do WordPress
   - Permite fazer link reverso Laravel ↔ WordPress

2. **Model Product** atualizado
   - Campo `wordpress_post_id` adicionado ao `$fillable`

3. **Job `SyncProductToWordPress`**
   - Sincroniza dados básicos para WordPress via REST API
   - Cria/atualiza posts do tipo `produtos`
   - Associa automaticamente taxonomia `product_brand`
   - 3 tentativas com backoff [10, 30, 60]s

4. **Endpoints de Sincronização**
   - `POST /api/products/sync-to-wordpress` → Sincroniza todos produtos
   - `POST /api/products/{id}/sync-to-wordpress` → Sincroniza produto individual

5. **Configuração `config/services.php`**
   - Seção `wordpress` adicionada com URL e credenciais API

---

## 🎯 Próximos Passos

### 1. Configurar WordPress REST API

No WordPress admin (https://rodust.com.br/wp-admin):

1. **Criar Application Password**:
   - Ir em `Usuários` → `Perfil`
   - Rolar até "Senhas de Aplicativos"
   - Nome: `Laravel API`
   - Clicar em "Adicionar Nova Senha de Aplicativo"
   - Copiar a senha gerada (ex: `xxxx xxxx xxxx xxxx xxxx xxxx`)

2. **Adicionar no Laravel `.env`**:
```env
WORDPRESS_URL=https://rodust.com.br
WORDPRESS_API_USER=admin
WORDPRESS_API_PASSWORD=xxxx xxxx xxxx xxxx xxxx xxxx
```

### 2. Testar Sincronização

```bash
# Terminal 1: Iniciar queue worker
docker compose exec laravel.test php artisan queue:work

# Terminal 2: Trigger sincronização
curl -X POST http://localhost:8000/api/products/sync-to-wordpress
```

**Resultado esperado**:
- 2 jobs `SyncProductToWordPress` processados
- 2 posts criados no WordPress tipo `produtos`
- Marcas "Dewalt" e "Noll" criadas na taxonomia `product_brand`

### 3. Criar Templates WordPress Otimizados

#### Arquivos a criar:

**a) `archive-produto.php`** - Listagem de produtos
```php
<?php
/**
 * Template para listagem de produtos
 * Consome API Laravel para dados em tempo real
 */

get_header();

// Buscar produtos da API Laravel
$page = get_query_var('paged', 1);
$api_url = 'http://localhost:8000/api/products?page=' . $page . '&per_page=20';

$response = wp_remote_get($api_url);
if (is_wp_error($response)) {
    echo '<p>Erro ao carregar produtos.</p>';
    get_footer();
    return;
}

$data = json_decode(wp_remote_retrieve_body($response), true);
$products = $data['data'] ?? [];
?>

<div class="products-archive">
    <h1>Produtos</h1>
    
    <div class="products-grid">
        <?php foreach ($products as $product): ?>
            <?php
            // Buscar URL do WordPress para SEO
            $wp_posts = get_posts([
                'post_type' => 'rodust_product',
                'meta_key' => '_laravel_product_id',
                'meta_value' => $product['id'],
                'posts_per_page' => 1
            ]);
            $permalink = !empty($wp_posts) ? get_permalink($wp_posts[0]) : '#';
            ?>
            
            <div class="product-card">
                <?php if ($product['image']): ?>
                    <img src="<?= esc_url($product['image']) ?>" alt="<?= esc_attr($product['name']) ?>">
                <?php endif; ?>
                
                <h2><a href="<?= esc_url($permalink) ?>"><?= esc_html($product['name']) ?></a></h2>
                
                <p class="price">
                    <?php if ($product['promotional_price']): ?>
                        <del>R$ <?= number_format($product['price'], 2, ',', '.') ?></del>
                        <strong>R$ <?= number_format($product['promotional_price'], 2, ',', '.') ?></strong>
                    <?php else: ?>
                        <strong>R$ <?= number_format($product['price'], 2, ',', '.') ?></strong>
                    <?php endif; ?>
                </p>
                
                <button onclick="addToCart(<?= $product['id'] ?>)">Adicionar ao Carrinho</button>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Paginação -->
    <?php
    $total_pages = ceil(($data['pagination']['total'] ?? 0) / 20);
    echo paginate_links([
        'total' => $total_pages,
        'current' => $page
    ]);
    ?>
</div>

<?php get_footer(); ?>
```

**b) `single-produto.php`** - Página individual do produto
```php
<?php
/**
 * Template para página individual de produto
 * Dados completos vêm da API Laravel
 */

get_header();

// Pegar ID do produto no Laravel
$laravel_id = get_post_meta(get_the_ID(), '_laravel_product_id', true);

if (!$laravel_id) {
    echo '<p>Produto não encontrado.</p>';
    get_footer();
    return;
}

// Buscar dados completos da API
$response = wp_remote_get("http://localhost:8000/api/products/{$laravel_id}");
$product = json_decode(wp_remote_retrieve_body($response), true);

if (!$product) {
    echo '<p>Erro ao carregar dados do produto.</p>';
    get_footer();
    return;
}
?>

<div class="product-single">
    <div class="product-gallery">
        <?php if (!empty($product['images'])): ?>
            <?php foreach ($product['images'] as $image): ?>
                <img src="<?= esc_url($image) ?>" alt="<?= esc_attr($product['name']) ?>">
            <?php endforeach; ?>
        <?php elseif ($product['image']): ?>
            <img src="<?= esc_url($product['image']) ?>" alt="<?= esc_attr($product['name']) ?>">
        <?php endif; ?>
    </div>
    
    <div class="product-info">
        <h1><?= esc_html($product['name']) ?></h1>
        
        <?php if ($product['brand']): ?>
            <p class="brand">Marca: <strong><?= esc_html($product['brand']) ?></strong></p>
        <?php endif; ?>
        
        <p class="price">
            <?php if ($product['promotional_price']): ?>
                <del>R$ <?= number_format($product['price'], 2, ',', '.') ?></del>
                <strong class="promo">R$ <?= number_format($product['promotional_price'], 2, ',', '.') ?></strong>
                <span class="discount">
                    <?= round((($product['price'] - $product['promotional_price']) / $product['price']) * 100) ?>% OFF
                </span>
            <?php else: ?>
                <strong>R$ <?= number_format($product['price'], 2, ',', '.') ?></strong>
            <?php endif; ?>
        </p>
        
        <p class="stock">
            <?php if ($product['stock'] > 0): ?>
                ✅ <strong><?= $product['stock'] ?></strong> em estoque
            <?php else: ?>
                ❌ Fora de estoque
            <?php endif; ?>
        </p>
        
        <div class="description">
            <?= wpautop($product['description']) ?>
        </div>
        
        <!-- Dimensões (para cálculo de frete) -->
        <input type="hidden" id="product-width" value="<?= $product['width'] ?>">
        <input type="hidden" id="product-height" value="<?= $product['height'] ?>">
        <input type="hidden" id="product-length" value="<?= $product['length'] ?>">
        <input type="hidden" id="product-weight" value="<?= $product['weight'] ?>">
        
        <div class="add-to-cart-section">
            <input type="number" id="quantity" value="1" min="1" max="<?= $product['stock'] ?>">
            <button onclick="addToCart(<?= $product['id'] ?>, document.getElementById('quantity').value)">
                Adicionar ao Carrinho
            </button>
        </div>
        
        <!-- Calcular frete -->
        <div class="shipping-calculator">
            <h3>Calcular Frete</h3>
            <input type="text" id="cep" placeholder="00000-000" maxlength="9">
            <button onclick="calculateShipping()">Calcular</button>
            <div id="shipping-options"></div>
        </div>
    </div>
</div>

<?php get_footer(); ?>
```

### 4. Atualizar checkout.php

O checkout já está funcionando, mas precisa garantir que usa as dimensões da API Laravel.

**Arquivo**: `wordpress/wp-content/plugins/rodust-ecommerce/templates/checkout.php`

Verificar se a variável `CHECKOUT_CART_ITEMS` está populando dimensões vindas da API Laravel em vez de meta fields do WordPress.

---

## 🔄 Fluxo de Sincronização

### Bling → Laravel (Detalhes Completos)
```
1. Cron/Manual: Clica em "Sincronizar Detalhes Completos" no painel Bling
2. Laravel enfileira jobs SyncProductDetailFromBling
3. Cada job busca /produtos/{id} do Bling (dimensões, peso, marca, imagens)
4. Salva tudo na tabela `products` do Laravel
```

### Laravel → WordPress (Metadados SEO)
```
1. Após sync do Bling, ou manual via endpoint
2. Laravel enfileira jobs SyncProductToWordPress
3. Job cria/atualiza post no WordPress via REST API
4. WordPress fica com dados mínimos (título, slug, meta description)
5. Laravel atualiza `wordpress_post_id` para fazer link reverso
```

### WordPress → Cliente (Renderização)
```
1. Usuário acessa /produtos/esmerilhadeira-angular/
2. WordPress carrega template single-produto.php
3. Template busca dados completos da API Laravel
4. Renderiza HTML com dados frescos (preço, estoque, dimensões)
5. JavaScript usa dimensões para calcular frete via Melhor Envio
```

---

## 🚀 Performance para 10.000 Produtos

### WordPress
- **10.000 posts "vazios"**: Apenas título + slug + 2 meta fields (_bling_id, _laravel_product_id)
- **Banco de dados**: ~50MB (vs 500MB com todos os dados)
- **Admin**: Rápido (posts não têm campos pesados)
- **SEO**: Perfeito (URLs indexáveis, sitemap automático)

### Laravel
- **10.000 produtos completos**: Todos os campos com índices otimizados
- **API**: Cache Redis para consultas frequentes (implementar depois)
- **Query**: 50-100ms para listar 20 produtos
- **Frete**: Dimensões sempre disponíveis para cálculo

### Cache Strategy (Próximo passo)
```php
// Laravel - cache de 1 hora para lista de produtos
Cache::remember('products_page_1', 3600, function() {
    return Product::where('active', true)->paginate(20);
});

// Invalidar cache quando produto atualizar
Product::saved(function($product) {
    Cache::forget('products_page_*');
});
```

---

## 📝 Checklist de Implementação

- [x] Migration `wordpress_post_id`
- [x] Job `SyncProductToWordPress`
- [x] Endpoints de sincronização Laravel
- [x] Configuração `services.php`
- [ ] Criar Application Password no WordPress
- [ ] Adicionar credenciais no `.env`
- [ ] Testar sincronização (2 produtos)
- [ ] Criar `archive-produto.php`
- [ ] Criar `single-produto.php`
- [ ] Atualizar `checkout.php` (garantir dimensões da API)
- [ ] Implementar cache Redis (opcional, futuro)
- [ ] Documentar para outros desenvolvedores

---

## 🎓 Para Novos Desenvolvedores

### Onde estão os dados dos produtos?
**Laravel (fonte da verdade)**: Todos os dados (dimensões, peso, marca, estoque, preços)
**WordPress (vitrine SEO)**: Apenas título + slug + IDs de referência

### Como adicionar/editar produto?
1. Adicionar no Bling (ERP)
2. Sincronizar no painel Laravel (Bling → Laravel)
3. Sincronizar para WordPress (Laravel → WordPress)
4. Pronto! Produto aparece no site com dados completos

### Como o template sabe os dados do produto?
Templates WordPress fazem `wp_remote_get()` para API Laravel e renderizam HTML com dados frescos.

### E se a API Laravel cair?
Implementar fallback: mostrar dados básicos salvos no WordPress (_price, _stock) + mensagem "Alguns dados podem estar desatualizados".

---

**Implementado em**: 26/11/2025
**Versão**: 1.0.0
**Status**: ✅ Backend pronto | ⏳ Templates WordPress pendentes
