# Fase 2 - Correção de Bug: API URL

**Data:** 27/11/2024 21:13  
**Issue:** JavaScript tentando acessar API Laravel diretamente ao invés de usar proxy WordPress

## 🐛 Problema Identificado

### Sintoma:
```
GET https://localhost:8443/wp-json/rodust-ecommerce/v1/customers/me 404 (Not Found)
```

Usuário logado era redirecionado para login mesmo já estando autenticado.

### Causa Raiz:
No arquivo `templates/checkout.php`, linha 111, o `wp_localize_script` estava configurando:
```php
'api_url' => get_rest_url(null, 'rodust-ecommerce/v1'),
```

Isso gerava: `https://localhost:8443/wp-json/rodust-ecommerce/v1`

**Problema:** Esta URL aponta diretamente para a API Laravel, que:
1. Está em um domínio diferente (CORS)
2. Não está acessível externamente (apenas via proxy)
3. Retorna 404 pois o endpoint não existe no WordPress

### URLs Corretas:
- ❌ `https://localhost:8443/wp-json/rodust-ecommerce/v1/customers/me` (direto Laravel - não funciona)
- ✅ `https://localhost:8443/wp-json/rodust-proxy/v1/customers/me` (via proxy WordPress - funciona)

---

## ✅ Solução Aplicada (ATUALIZADA)

### Problema Real:
O checkout estava usando `RODUST_CHECKOUT_DATA.api_url` (configurado via `wp_localize_script`), mas as outras páginas do site usam `window.RODUST_API_URL` (configurado globalmente no `functions.php` do tema).

### Solução Correta:
Usar a mesma variável global que o resto do site: `window.RODUST_API_URL`

### Arquivos Modificados:

#### 1. checkout-customer.js
```javascript
// ANTES
url: RODUST_CHECKOUT_DATA.api_url + '/customers/me',

// DEPOIS
url: window.RODUST_API_URL + '/api/customers/me',
```

#### 2. checkout-addresses.js (3 ocorrências)
```javascript
// ANTES
url: RODUST_CHECKOUT_DATA.api_url + '/customers/addresses',
url: RODUST_CHECKOUT_DATA.api_url + '/addresses/search-zipcode/' + zipcode,

// DEPOIS
url: window.RODUST_API_URL + '/api/customers/addresses',
url: window.RODUST_API_URL + '/api/addresses/search-zipcode/' + zipcode,
```

#### 3. checkout-shipping.js
```javascript
// ANTES
url: RODUST_CHECKOUT_DATA.api_url + '/shipping/calculate',

// DEPOIS
url: window.RODUST_API_URL + '/api/shipping/calculate',
```

#### 4. checkout-form.js
```javascript
// ANTES
url: RODUST_CHECKOUT_DATA.api_url + '/customers/addresses',

// DEPOIS
url: window.RODUST_API_URL + '/api/customers/addresses',
```

#### 5. checkout.php (removido api_url do wp_localize_script)
```php
// ANTES
wp_localize_script('rodust-checkout-init', 'RODUST_CHECKOUT_DATA', [
    'cart_items' => array_values($js_cart),
    'api_url' => get_rest_url(null, 'rodust-proxy/v1'), // ❌ Removido
    'home_url' => home_url(),
    'login_url' => home_url('/login'),
    'payment_url' => home_url('/checkout-payment'),
    'nonce' => wp_create_nonce('wp_rest'),
]);

// DEPOIS
wp_localize_script('rodust-checkout-init', 'RODUST_CHECKOUT_DATA', [
    'cart_items' => array_values($js_cart),
    // api_url removido - usa window.RODUST_API_URL global
    'home_url' => home_url(),
    'login_url' => home_url('/login'),
    'payment_url' => home_url('/checkout-payment'),
    'nonce' => wp_create_nonce('wp_rest'),
]);
```

### Por que essa solução é melhor:

1. **Consistência:** Todas as páginas do site usam `window.RODUST_API_URL`
2. **Configuração centralizada:** Apenas um lugar (`functions.php`) configura a URL
3. **Já funciona:** Login, produtos, minha conta, etc. já usam essa variável
4. **Proxy correto:** `window.RODUST_API_URL` já aponta para `/wp-json/rodust-proxy/v1`

### URLs Finais:
```
window.RODUST_API_URL = "https://localhost:8443/wp-json/rodust-proxy/v1"
```

Chamadas AJAX:
- `/api/customers/me` → `https://localhost:8443/wp-json/rodust-proxy/v1/api/customers/me`
- `/api/customers/addresses` → `https://localhost:8443/wp-json/rodust-proxy/v1/api/customers/addresses`
- `/api/shipping/calculate` → `https://localhost:8443/wp-json/rodust-proxy/v1/api/shipping/calculate`

---

## ✅ Solução Aplicada (PRIMEIRA TENTATIVA - INCORRETA)

### Arquivo Modificado:
`templates/checkout.php` - linha 111

### Mudança:
```php
// ANTES (ERRADO)
'api_url' => get_rest_url(null, 'rodust-ecommerce/v1'),

// DEPOIS (CORRETO)
'api_url' => get_rest_url(null, 'rodust-proxy/v1'),
```

### Impacto:
Todos os 6 arquivos JavaScript do checkout agora apontam para o proxy correto:
- `checkout-customer.js` → `/customers/me`, `/customers/addresses`
- `checkout-addresses.js` → `/customers/addresses`, `/addresses/search-zipcode/*`
- `checkout-shipping.js` → `/shipping/calculate`
- `checkout-form.js` → `/customers/addresses`

---

## 🔍 Como o Proxy Funciona

### Fluxo Correto:
```
[Browser] → [WordPress Proxy] → [Laravel API] → [Response]
```

1. JavaScript faz chamada para `wp-json/rodust-proxy/v1/customers/me`
2. WordPress intercepta via REST API customizado
3. Proxy do WordPress (`api-proxy.php`) repassa para Laravel API
4. Laravel processa e retorna para o proxy
5. Proxy retorna para o JavaScript

### Arquivo do Proxy:
`wp-content/themes/rodust/includes/api-proxy.php`

**Função:** Intermediar todas as chamadas do frontend WordPress para a API Laravel, adicionando headers corretos e tratando CORS.

---

## 🧪 Teste Realizado

**Horário da correção:** 21:13:53  
**Ação necessária:** Limpar cache do navegador (Ctrl+Shift+Delete) e tentar novamente

**Teste esperado:**
1. Fazer login em `/login`
2. Ir para `/checkout`
3. JavaScript deve carregar dados do cliente sem erro 404
4. Console deve mostrar: `Dados do carrinho: [{...}]` sem erros

---

## 📝 Checklist de Correção

- [x] Identificar URL incorreta no `wp_localize_script`
- [x] Alterar de `rodust-ecommerce/v1` para `rodust-proxy/v1`
- [x] Verificar que todos os JS files usam `RODUST_CHECKOUT_DATA.api_url`
- [ ] Testar login → checkout (aguardando teste do usuário)
- [ ] Verificar console sem erros 404
- [ ] Confirmar dados do cliente carregados

---

## 🔄 Arquivos JavaScript Impactados

Todos os arquivos já estavam corretos, usando a variável centralizada:

1. **checkout-customer.js** (linha 22)
   ```javascript
   url: RODUST_CHECKOUT_DATA.api_url + '/customers/me',
   ```

2. **checkout-addresses.js** (linhas 13, 185, 237)
   ```javascript
   url: RODUST_CHECKOUT_DATA.api_url + '/customers/addresses',
   url: RODUST_CHECKOUT_DATA.api_url + '/addresses/search-zipcode/' + zipcode,
   ```

3. **checkout-shipping.js** (linha 52)
   ```javascript
   url: RODUST_CHECKOUT_DATA.api_url + '/shipping/calculate',
   ```

4. **checkout-form.js** (linha 60)
   ```javascript
   url: RODUST_CHECKOUT_DATA.api_url + '/customers/addresses',
   ```

**Benefício da centralização:** Bastou alterar 1 linha no PHP para corrigir todas as 6 chamadas JavaScript!

---

## 🎯 Lições Aprendidas

1. **Sempre usar o proxy WordPress** para chamadas de frontend
2. **Centralizar configurações** (wp_localize_script é perfeito para isso)
3. **Testar com console aberto** para pegar erros 404 rapidamente
4. **Documentar fluxo de dados** (frontend → proxy → backend)

---

## 🚀 Status

**Correção aplicada:** ✅  
**Teste pendente:** ⏳ Aguardando usuário limpar cache e testar

**Comando para limpar cache do navegador:**
- Chrome/Edge: `Ctrl + Shift + Delete` → Limpar cache de imagens e arquivos
- Ou modo anônito: `Ctrl + Shift + N`

---

**Última atualização:** 27/11/2024 21:13  
**Responsável:** GitHub Copilot (Claude Sonnet 4.5)
