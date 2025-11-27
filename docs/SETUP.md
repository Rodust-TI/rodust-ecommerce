# 🚀 Setup do Projeto - Rodust Ecommerce

## 📋 Pré-requisitos

- **Docker Desktop** instalado e rodando
- **Git** instalado
- **VS Code** (recomendado)
- **Composer** (opcional, já vem no container)

---

## 🔧 Configuração Inicial

### 1. Clone o Repositório

```bash
git clone https://github.com/Rodust-TI/rodust-ecommerce.git
cd rodust-ecommerce
```

### 2. Configure o Arquivo `.env`

Copie o arquivo de exemplo e ajuste as variáveis:

```bash
cp .env.example .env
```

**Variáveis importantes:**

```env
APP_NAME="Rodust Ecommerce"
APP_ENV=local
APP_KEY=base64:sua-chave-aqui
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=ecommerce
DB_USERNAME=sail
DB_PASSWORD=password

# Bling API
BLING_CLIENT_ID=seu_client_id
BLING_CLIENT_SECRET=seu_client_secret
BLING_STATE=seu_state_aleatorio
BLING_CUSTOMER_TYPE_ID=id_tipo_contato

# WordPress Integration
WORDPRESS_URL=http://localhost:8080
WORDPRESS_API_URL=http://localhost:8080/wp-json
```

### 3. Inicie os Containers Docker

```bash
docker compose up -d
```

**Containers criados:**
- `laravel.test` - Aplicação Laravel (porta 80)
- `mysql` - Banco de dados MySQL (porta 3306)
- `redis` - Cache Redis (porta 6379)
- `mailpit` - Servidor de email local (porta 1025/8025)

### 4. Instale as Dependências

```bash
docker compose exec laravel.test composer install
```

### 5. Gere a Chave da Aplicação

```bash
docker compose exec laravel.test php artisan key:generate
```

### 6. Execute as Migrations

```bash
docker compose exec laravel.test php artisan migrate
```

### 7. Execute os Seeders (se houver)

```bash
docker compose exec laravel.test php artisan db:seed
```

---

## ⚙️ Comandos Úteis

### Docker

```bash
# Iniciar containers
docker compose up -d

# Parar containers
docker compose down

# Ver logs
docker compose logs -f

# Reconstruir containers
docker compose up -d --build
```

### Laravel Artisan

```bash
# Acessar o container
docker compose exec laravel.test bash

# Limpar cache
docker compose exec laravel.test php artisan cache:clear
docker compose exec laravel.test php artisan config:clear
docker compose exec laravel.test php artisan route:clear
docker compose exec laravel.test php artisan view:clear

# Rodar migrations
docker compose exec laravel.test php artisan migrate

# Rollback migrations
docker compose exec laravel.test php artisan migrate:rollback

# Criar migration
docker compose exec laravel.test php artisan make:migration nome_da_migration

# Criar model
docker compose exec laravel.test php artisan make:model NomeModel -m

# Criar controller
docker compose exec laravel.test php artisan make:controller NomeController
```

### Queue Worker

```bash
# Iniciar queue worker
docker compose exec laravel.test php artisan queue:work --tries=3 --timeout=300

# Reiniciar queue worker
docker compose exec laravel.test php artisan queue:restart
```

---

## 🔄 Integração com Bling

### Comandos Bling

```bash
# Testar autenticação OAuth
docker compose exec laravel.test php artisan bling:test-auth

# Sincronizar produtos do Bling
docker compose exec laravel.test php artisan bling:sync-products

# Sincronizar produtos para WordPress
docker compose exec laravel.test php artisan products:sync-to-wordpress

# Testar contato no Bling
docker compose exec laravel.test php artisan bling:test-contact update-pj {bling_id}

# Testar sincronização de endereços
docker compose exec laravel.test php artisan bling:test-address-sync {customer_id}

# Listar clientes com endereços
docker compose exec laravel.test php artisan customers:list-with-addresses
```

### Fluxo de Sincronização

1. **Produtos Bling → Laravel:**
   ```bash
   docker compose exec laravel.test php artisan bling:sync-products
   ```

2. **Produtos Laravel → WordPress:**
   ```bash
   docker compose exec laravel.test php artisan products:sync-to-wordpress
   ```

---

## 🔌 Integração WordPress

### Requisitos WordPress

1. Plugin **Rodust Ecommerce** instalado e ativo
2. Endpoint REST API: `{wordpress_url}/wp-json/rodust/v1/products`

### Estrutura de Endereços

- **shipping** → `endereco.geral` no Bling
- **billing** → `endereco.cobranca` no Bling
- **NULL** (adicional) → Apenas local, não sincroniza

### Sincronização Automática

Endereços são sincronizados automaticamente com Bling quando:
- Cliente cria novo endereço shipping/billing
- Cliente atualiza endereço shipping/billing

---

## 🧪 Testes

### Testar Sincronização Completa

```bash
# 1. Listar clientes
docker compose exec laravel.test php artisan customers:list-with-addresses

# 2. Testar sincronização de endereços (use ID do cliente)
docker compose exec laravel.test php artisan bling:test-address-sync 1

# 3. Verificar logs
docker compose exec laravel.test tail -50 storage/logs/laravel.log
```

---

## 📦 Estrutura do Projeto

```
ecommerce/
├── app/
│   ├── Console/Commands/      # Comandos Artisan customizados
│   ├── Http/Controllers/      # Controllers da API
│   │   └── API/
│   ├── Models/                # Models Eloquent
│   ├── Services/              # Services (BlingCustomerService, etc)
│   └── Jobs/                  # Jobs de fila
├── database/
│   ├── migrations/            # Migrations do banco
│   └── seeders/               # Seeders
├── routes/
│   ├── api.php               # Rotas da API
│   └── web.php               # Rotas web
├── storage/
│   └── logs/                 # Logs da aplicação
├── docker-compose.yml        # Configuração Docker
└── .env                      # Variáveis de ambiente
```

---

## 🐛 Troubleshooting

### Container não inicia

```bash
docker compose down
docker compose up -d --build
```

### Erro de permissão

```bash
docker compose exec laravel.test chmod -R 777 storage bootstrap/cache
```

### Banco de dados não conecta

Verifique se o container MySQL está rodando:
```bash
docker compose ps
```

### Queue não processa jobs

Reinicie o worker:
```bash
docker compose exec laravel.test php artisan queue:restart
```

### Erro "Class not found"

```bash
docker compose exec laravel.test composer dump-autoload
```

---

## 🔐 Credenciais Padrão

### Banco de Dados (Local)
- **Host:** localhost:3306
- **Database:** ecommerce
- **User:** sail
- **Password:** password

### Mailpit (Email Local)
- **Web:** http://localhost:8025
- **SMTP:** localhost:1025

---

## 📝 Notas Importantes

1. **Limite de endereços:** Cada cliente pode ter no máximo 5 endereços
2. **Tipos de endereços:**
   - `shipping` (entrega) → sincroniza com Bling
   - `billing` (cobrança) → sincroniza com Bling
   - `NULL` (adicional) → apenas local

3. **Meta fields WordPress:** Todos usam prefixo underscore:
   - `_sku`, `_price`, `_stock`, `_bling_id`, `_laravel_id`

4. **Bling PUT:** Sempre envia payload completo do cliente para não perder dados

---

## 🆘 Suporte

Em caso de dúvidas ou problemas:
1. Verificar logs: `storage/logs/laravel.log`
2. Consultar documentação do Laravel: https://laravel.com/docs
3. Consultar documentação da API Bling: https://developer.bling.com.br/

---

**Última atualização:** 26/11/2025
