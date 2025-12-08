# 📜 Scripts de Desenvolvimento

Esta pasta contém scripts utilitários para desenvolvimento, debug e manutenção do projeto.

> ⚠️ **IMPORTANTE**: Todos estes scripts são de **DESENVOLVIMENTO/TESTE/DEBUG**.  
> **NENHUM deles faz parte do fluxo automático de produção.**  
> O sistema funciona automaticamente via Jobs, Webhooks e Observers.  
> Veja [`ANALISE-PRODUCAO.md`](./ANALISE-PRODUCAO.md) para detalhes completos.

## 📁 Estrutura

```
scripts/
├── debug/              # Scripts de debug e inspeção
├── maintenance/        # Scripts de manutenção e sincronização
├── utils/              # Utilitários diversos
└── README.md           # Este arquivo
```

## 🐛 Debug (`debug/`)

Scripts para inspecionar dados e debugar problemas:

- `check-order.php` - Verificar detalhes de um pedido específico
- `list-orders.php` - Listar últimos 10 pedidos
- `list-payment-methods.php` - Listar métodos de pagamento disponíveis
- `list-products-dimensions.php` - Listar dimensões de produtos
- `list-products.php` - Listar produtos

### Uso

```powershell
# Executar dentro do container Laravel
docker exec -it docker-laravel.test-1 php scripts/debug/list-orders.php
```

## 🔧 Manutenção (`maintenance/`)

Scripts para manutenção e sincronização com sistemas externos:

- `refresh-bling-token.php` - Renovar token de autenticação do Bling
- `resend-order-to-bling.php` - Reenviar pedido ao Bling
- `reset-orders.php` - Resetar pedidos (cuidado!)
- `update-order-bling-number.php` - Atualizar número do pedido no Bling

### Uso

```powershell
# Renovar token Bling
docker exec -it docker-laravel.test-1 php scripts/maintenance/refresh-bling-token.php

# Reenviar pedido ao Bling
docker exec -it docker-laravel.test-1 php scripts/maintenance/resend-order-to-bling.php 123
```

## 🛠️ Utilitários (`utils/`)

Scripts utilitários diversos:

- `generate-reset-token.php` - Gerar token de reset de senha para cliente

### Uso

```powershell
docker exec -it docker-laravel.test-1 php scripts/utils/generate-reset-token.php
```

## ⚠️ Importante

- **Nunca execute scripts diretamente no Windows** - sempre use dentro do container Docker
- Scripts de manutenção podem modificar dados - use com cuidado
- Alguns scripts podem precisar de parâmetros - verifique o código antes de executar

## 🔄 Migrando para Comandos Artisan

Alguns scripts podem ser convertidos em comandos Artisan para melhor integração:

```bash
# Exemplo: converter list-orders.php em comando
php artisan make:command ListOrders
```

Isso permite usar:
```bash
php artisan list:orders
```

---

**Última atualização:** Dezembro 2025

