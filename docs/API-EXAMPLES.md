# Testes da API - Rodust Ecommerce

## Requisitos

- Containers Docker rodando
- Worker de filas ativo: `docker compose exec laravel.test php artisan queue:work redis`

## 1. Produtos

### Listar todos os produtos

```http
GET http://localhost/api/products
```

### Listar produtos com busca

```http
GET http://localhost/api/products?search=teste&per_page=10
```

### Ver um produto específico

```http
GET http://localhost/api/products/1
```

### Criar produto (requer autenticação)

```http
POST http://localhost/api/admin/products
Content-Type: application/json
Authorization: Bearer YOUR_SANCTUM_TOKEN

{
  "sku": "PROD-001",
  "name": "Camiseta Básica",
  "description": "Camiseta 100% algodão",
  "price": 49.90,
  "cost": 25.00,
  "stock": 50,
  "image": "https://example.com/image.jpg",
  "active": true
}
```

### Atualizar produto (requer autenticação)

```http
PUT http://localhost/api/admin/products/1
Content-Type: application/json
Authorization: Bearer YOUR_SANCTUM_TOKEN

{
  "price": 59.90,
  "stock": 45
}
```

### Deletar produto (requer autenticação)

```http
DELETE http://localhost/api/admin/products/1
Authorization: Bearer YOUR_SANCTUM_TOKEN
```

## 2. Pedidos

### Criar pedido (Checkout)

```http
POST http://localhost/api/orders
Content-Type: application/json

{
  "customer": {
    "name": "João Silva",
    "email": "joao@example.com",
    "phone": "11999999999",
    "cpf_cnpj": "12345678900",
    "zipcode": "01310-100",
    "address": "Av. Paulista",
    "number": "1000",
    "complement": "Apto 101",
    "neighborhood": "Bela Vista",
    "city": "São Paulo",
    "state": "SP"
  },
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    },
    {
      "product_id": 2,
      "quantity": 1
    }
  ],
  "shipping": 15.00,
  "discount": 10.00,
  "payment_method": "credit_card"
}
```

### Ver detalhes de um pedido

```http
GET http://localhost/api/orders/1
```

### Listar pedidos (requer autenticação)

```http
GET http://localhost/api/admin/orders
Authorization: Bearer YOUR_SANCTUM_TOKEN
```

### Listar pedidos por status (requer autenticação)

```http
GET http://localhost/api/admin/orders?status=pending&per_page=20
Authorization: Bearer YOUR_SANCTUM_TOKEN
```

### Atualizar status do pedido (requer autenticação)

```http
PUT http://localhost/api/admin/orders/1
Content-Type: application/json
Authorization: Bearer YOUR_SANCTUM_TOKEN

{
  "status": "paid",
  "payment_status": "paid",
  "notes": "Pagamento confirmado via cartão"
}
```

## 3. Exemplos com cURL

### Criar produto

```bash
curl -X POST http://localhost/api/admin/products \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "sku": "PROD-002",
    "name": "Calça Jeans",
    "price": 129.90,
    "stock": 30,
    "active": true
  }'
```

### Criar pedido

```bash
curl -X POST http://localhost/api/orders \
  -H "Content-Type: application/json" \
  -d '{
    "customer": {
      "name": "Maria Santos",
      "email": "maria@example.com",
      "phone": "11988888888"
    },
    "items": [
      {
        "product_id": 1,
        "quantity": 1
      }
    ],
    "shipping": 20.00,
    "payment_method": "pix"
  }'
```

### Listar produtos

```bash
curl http://localhost/api/products
```

## 4. Respostas Esperadas

### Sucesso - Lista de Produtos

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "sku": "PROD-001",
      "name": "Camiseta Básica",
      "description": "Camiseta 100% algodão",
      "price": "49.90",
      "cost": "25.00",
      "stock": 50,
      "image": "https://example.com/image.jpg",
      "active": true,
      "bling_id": "12345",
      "bling_synced_at": "2025-01-13 10:30:00",
      "created_at": "2025-01-13T10:00:00.000000Z",
      "updated_at": "2025-01-13T10:30:00.000000Z"
    }
  ],
  "per_page": 15,
  "total": 1
}
```

### Sucesso - Pedido Criado

```json
{
  "id": 1,
  "customer_id": 1,
  "order_number": "ORD-1705147200",
  "status": "pending",
  "subtotal": "99.80",
  "discount": "10.00",
  "shipping": "15.00",
  "total": "104.80",
  "payment_method": "credit_card",
  "payment_status": "pending",
  "notes": null,
  "bling_id": null,
  "bling_synced_at": null,
  "created_at": "2025-01-13T11:00:00.000000Z",
  "updated_at": "2025-01-13T11:00:00.000000Z",
  "customer": {
    "id": 1,
    "name": "João Silva",
    "email": "joao@example.com",
    "phone": "11999999999"
  },
  "items": [
    {
      "id": 1,
      "order_id": 1,
      "product_id": 1,
      "product_name": "Camiseta Básica",
      "product_sku": "PROD-001",
      "quantity": 2,
      "unit_price": "49.90",
      "total_price": "99.80"
    }
  ]
}
```

### Erro - Validação

```json
{
  "errors": {
    "sku": [
      "The sku field is required."
    ],
    "price": [
      "The price must be at least 0."
    ]
  }
}
```

### Erro - Estoque Insuficiente

```json
{
  "error": "Estoque insuficiente para o produto Camiseta Básica"
}
```

## 5. Status Disponíveis

### Status de Pedido
- `pending` - Pendente
- `paid` - Pago
- `processing` - Processando
- `shipped` - Enviado
- `delivered` - Entregue
- `cancelled` - Cancelado

### Status de Pagamento
- `pending` - Pendente
- `paid` - Pago
- `failed` - Falhou
- `refunded` - Reembolsado

## 6. Autenticação (Sanctum)

Para usar rotas protegidas, primeiro crie um token:

```php
// No tinker
docker compose exec laravel.test php artisan tinker

$user = User::first();
$token = $user->createToken('api-token')->plainTextToken;
echo $token;
```

Use o token nas requisições:
```
Authorization: Bearer 1|abc123def456...
```
