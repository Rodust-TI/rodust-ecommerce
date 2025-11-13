# 🚀 Guia Rápido - Próximos Passos

## Status Atual ✅

- ✅ Laravel instalado
- ✅ Sail configurado (MySQL + Redis)
- ✅ Models e migrations criados (Product, Customer, Order, OrderItem)
- ✅ Integração Bling implementada (BlingService)
- ✅ Jobs para sincronização (SyncProductToBling, SyncOrderToBling)
- ✅ Controllers de API (ProductController, OrderController)
- ✅ Rotas de API configuradas
- ✅ Documentação de integração WordPress criada
- ✅ Filas configuradas com Redis

## 📝 Respostas às Suas Dúvidas

### 1. Docker e Múltiplos Projetos

**Não há risco de conflito!** 

- Seu outro projeto Laravel (que vi nos containers rodando) está completamente isolado
- Cada projeto Sail cria sua própria rede Docker e volumes
- Docker Desktop gerencia apenas containers **locais** - não afeta projetos em outros servidores
- Os containers que vi (`laravel_nginx`, `laravel_app`, `laravel_db_backup`) são do outro projeto e continuarão funcionando normalmente

### 2. Arquivos no SSD Externo

**Sim, é possível e é a configuração atual!**

- ✅ Arquivos ficam em `M:\Websites\rodust.com.br\ecommerce`
- ✅ Containers Linux executam via Docker Desktop + WSL2
- ✅ Performance adequada para desenvolvimento
- ✅ Total portabilidade entre computadores

**Como funciona:**
```
SSD M:\ (Windows)  →  Docker Desktop (WSL2)  →  Container Linux
     ↓                        ↓                       ↓
  Arquivos          Volume Bind Mount           Execução
```

### 3. Warnings de Classes Duplicadas

**NÃO é por causa do outro projeto Laravel!**

Causas:
- Ocorre quando pacotes do Composer têm arquivos em locais duplicados no `vendor/`
- É um aviso do autoloader, não afeta funcionamento
- Comum em projetos novos Laravel 12

Solução (opcional): Já documentei no README como suprimir esses avisos se incomodar.

### 4. Montar SSD Diretamente no WSL

**Não é necessário** para seu caso de uso, mas é possível:

**Método Simples (atual):**
```powershell
# Arquivos em M:\ são acessados via /mnt/m no WSL
# Docker Desktop faz isso automaticamente
```

**Método Avançado (mount nativo):**
```powershell
# Requer admin e identifica o disco físico
wsl --mount \\.\PHYSICALDRIVE2 --bare
# Depois cria partição no WSL
```

**Recomendação:** Use o método atual (mais simples e funciona bem).

## ▶️ Como Continuar AGORA

### Opção A: Aguardar Build Terminar

O build da imagem Docker está rodando. Pode demorar 5-10 minutos na primeira vez.

**Verificar progresso:**
```powershell
# Em outro terminal
docker ps -a
```

**Quando terminar:**
```powershell
cd 'M:\Websites\rodust.com.br\ecommerce'
$env:WWWUSER="1000"; $env:WWWGROUP="1000"
docker compose up -d
```

### Opção B: Usar Atalho que Criei

Criei um script `sail.ps1` que facilita o uso, mas precisa de ajuste (bash não encontrado no WSL).

**Solução temporária - use comandos diretos:**
```powershell
# Subir containers
cd 'M:\Websites\rodust.com.br\ecommerce'
$env:WWWUSER="1000"
## 🎯 Próximos Passos

### 1. Configurar Bling API

Edite o arquivo `.env` e adicione sua chave da API do Bling:

```env
BLING_API_KEY=sua-chave-bling-aqui
BLING_BASE_URL=https://bling.com.br/Api/v2
```

### 2. Testar a API

```bash
# Iniciar worker de filas (em um terminal separado)
docker compose exec laravel.test php artisan queue:work redis

# Criar um produto de teste
docker compose exec laravel.test php artisan tinker
```

No Tinker:
```php
$product = App\Models\Product::create([
    'sku' => 'TEST-001',
    'name' => 'Produto Teste',
    'description' => 'Descrição do produto',
    'price' => 99.90,
    'cost' => 50.00,
    'stock' => 10,
    'active' => true,
]);

// Disparar sincronização com Bling
App\Jobs\SyncProductToBling::dispatch($product);
```

### 3. Testar Endpoints da API

```bash
# Listar produtos
curl http://localhost/api/products

# Ver um produto
curl http://localhost/api/products/1

# Criar pedido (checkout)
curl -X POST http://localhost/api/orders \
  -H "Content-Type: application/json" \
  -d '{
    "customer": {
      "name": "João Silva",
      "email": "joao@example.com",
      "phone": "11999999999"
    },
    "items": [
      {
        "product_id": 1,
        "quantity": 2
      }
    ],
    "shipping": 15.00,
    "payment_method": "credit_card"
  }'
```

### 4. Configurar WordPress

Siga o guia completo em **`INTEGRACAO-WORDPRESS.md`**:

1. Instalar WordPress em um diretório/subdomínio separado
2. Criar plugin customizado para consumir a API Laravel
3. Adicionar shortcodes para exibir produtos
4. Implementar JavaScript para carrinho e checkout

### 5. Tarefas Opcionais

- [ ] Criar seeders para popular banco com dados de teste
- [ ] Adicionar autenticação Sanctum para área administrativa
- [ ] Implementar webhook do Bling para sincronização bidirecional
- [ ] Adicionar cache Redis para consultas de produtos
- [ ] Configurar CORS para o domínio WordPress em produção
- [ ] Implementar gateway de pagamento (Mercado Pago, PagSeguro)
- [ ] Adicionar cálculo de frete via API dos Correios

## 📂 Estrutura do Projeto

```
app/
├── Models/
│   ├── Product.php          # Model de produtos
│   ├── Customer.php         # Model de clientes
│   ├── Order.php           # Model de pedidos
│   └── OrderItem.php       # Model de itens do pedido
├── Services/
│   └── BlingService.php    # Serviço de integração com Bling
├── Jobs/
│   ├── SyncProductToBling.php   # Job de sincronização de produtos
│   └── SyncOrderToBling.php     # Job de sincronização de pedidos
└── Http/Controllers/Api/
    ├── ProductController.php    # Controller de produtos
    └── OrderController.php      # Controller de pedidos

database/migrations/
├── *_create_products_table.php
├── *_create_customers_table.php
├── *_create_orders_table.php
└── *_create_order_items_table.php

routes/
└── api.php                 # Rotas da API REST

config/
└── services.php            # Configuração do Bling
```

## 🔄 Fluxo de Sincronização

### Produto Laravel → Bling

1. Criar/atualizar produto no Laravel
2. Job `SyncProductToBling` é disparado
3. `BlingService` envia dados via API
4. Bling retorna ID, Laravel salva em `bling_id`

### Pedido WordPress → Laravel → Bling

1. Cliente finaliza compra no WordPress
2. WordPress envia POST para `/api/orders`
3. Laravel cria pedido e itens
4. Job `SyncOrderToBling` é disparado
5. `BlingService` envia pedido para Bling
6. Estoque é atualizado automaticamente

## 🛠️ Comandos Úteis

```bash
# Iniciar containers
docker compose up -d

# Ver logs
docker compose logs -f laravel.test

# Worker de filas
docker compose exec laravel.test php artisan queue:work redis

# Rodar migrations
docker compose exec laravel.test php artisan migrate

# Criar migration
docker compose exec laravel.test php artisan make:migration nome_da_migration

# Criar model
docker compose exec laravel.test php artisan make:model NomeModel

# Criar controller
docker compose exec laravel.test php artisan make:controller NomeController

# Limpar cache
docker compose exec laravel.test php artisan cache:clear
docker compose exec laravel.test php artisan config:clear

# Acessar MySQL
docker compose exec mysql mysql -u sail -ppassword laravel
```

---

**Próximo Passo:** Abrir terminal WSL e rodar `./vendor/bin/sail up -d` 🚀
