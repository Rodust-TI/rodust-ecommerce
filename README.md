# E-commerce Laravel + Bling ERP

Projeto Laravel para e-commerce integrado ao Bling como ERP, com front-end WordPress.

## 📋 Requisitos

- Docker Desktop com WSL2 habilitado
- Git
- Composer (para instalação inicial no Windows)

## 🚀 Configuração Inicial

### 1. Setup do Ambiente

O projeto está configurado para rodar com **Laravel Sail** (Docker). Os arquivos ficam no SSD externo (`M:\`) mas são executados dentro de containers Linux para melhor performance e compatibilidade.

### 2. Iniciar os Containers

No **PowerShell** (Windows), na raiz do projeto:

```powershell
# Subir os containers (primeira vez pode demorar para fazer build)
.\vendor\bin\sail up -d

# Verificar status dos containers
.\vendor\bin\sail ps
```

**Atalho recomendado**: Crie um alias para facilitar:

```powershell
# Adicione ao seu perfil do PowerShell ($PROFILE)
function sail { .\vendor\bin\sail.ps1 $args }

# Depois use apenas:
sail up -d
sail ps
sail artisan migrate
```

### 3. Configurar Banco de Dados

O `.env` já está configurado para usar os containers Docker:

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
```

Executar migrations:

```powershell
.\vendor\bin\sail artisan migrate
```

### 4. Acessar a Aplicação

- **Aplicação Laravel**: http://localhost
- **MySQL**: `localhost:3306` (credenciais: `sail` / `password`)
- **Redis**: `localhost:6379`

## 🔧 Comandos Úteis do Sail

```powershell
# Iniciar containers
.\vendor\bin\sail up -d

# Parar containers
.\vendor\bin\sail down

# Ver logs
.\vendor\bin\sail logs -f

# Executar comandos Artisan
.\vendor\bin\sail artisan [comando]

# Executar Composer dentro do container
.\vendor\bin\sail composer [comando]

# Executar NPM dentro do container
.\vendor\bin\sail npm [comando]

# Acessar shell do container
.\vendor\bin\sail shell

# Executar testes
.\vendor\bin\sail test
```

## 📦 Pacotes Instalados

Após subir os containers, instale os pacotes essenciais:

```powershell
.\vendor\bin\sail composer require guzzlehttp/guzzle
.\vendor\bin\sail composer require laravel/sanctum
.\vendor\bin\sail composer require spatie/laravel-permission
```

## 🔌 Integração com Bling

### Configuração

Adicione as credenciais do Bling no `.env`:

```env
BLING_API_KEY=seu_token_aqui
BLING_BASE_URL=https://bling.com.br/Api/v2
```

### Estrutura de Serviços

O serviço de integração fica em `app/Services/BlingService.php`.

## 🔄 WordPress + Laravel (Arquitetura Headless)

### Abordagem Recomendada

1. **WordPress**: Front-end público (site, catálogo, conteúdo)
2. **Laravel API**: Backend do e-commerce (cart, checkout, pedidos)
3. **Bling**: ERP (estoque, produtos, fulfillment)

### Fluxo de Dados

```
WordPress (Front) → Laravel API → Bling ERP
     ↓                    ↓
  Conteúdo          Transações
```

### Autenticação WordPress → Laravel

Use Laravel Sanctum para gerar tokens de API:

```powershell
.\vendor\bin\sail artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
.\vendor\bin\sail artisan migrate
```

## 💾 Trabalhando com SSD Externo

### ✅ Vantagens da Configuração Atual

- **Portabilidade**: Leve o SSD entre computadores
- **Containers isolados**: Ambiente Linux rodando no Windows via Docker
- **Performance aceitável**: Docker Desktop otimiza I/O para volumes do Windows

### ⚡ Performance

O Docker Desktop com WSL2 otimiza automaticamente o acesso aos volumes do Windows (`/mnt/m`). Para projetos Laravel, a performance é adequada para desenvolvimento.

**Dica**: Se precisar de máxima performance, copie o projeto para o filesystem do WSL temporariamente:

```bash
# No WSL (Ubuntu/Debian)
cp -r /mnt/m/Websites/rodust.com.br/ecommerce ~/projetos/
cd ~/projetos/ecommerce
./vendor/bin/sail up -d
```

## 🐳 Docker e Múltiplos Projetos

### Separação de Projetos

Cada projeto Laravel com Sail cria:
- **Rede própria**: `ecommerce_sail` (nome baseado na pasta)
- **Volumes próprios**: `ecommerce_sail-mysql`, `ecommerce_sail-redis`
- **Containers próprios**: prefixados com nome da pasta

### ⚠️ Não Há Risco de Conflito

Estar logado no Docker Desktop **NÃO afeta** projetos em outros servidores. Docker Desktop gerencia apenas containers **locais** na sua máquina. Projetos em outros servidores Linux rodam de forma completamente independente.

### Evitar Conflitos de Porta

Se rodar múltiplos projetos simultaneamente, ajuste as portas no `.env`:

```env
# Projeto 1 (padrão)
APP_PORT=80
FORWARD_DB_PORT=3306
FORWARD_REDIS_PORT=6379

# Projeto 2 (portas alternativas)
APP_PORT=8080
FORWARD_DB_PORT=3307
FORWARD_REDIS_PORT=6380
```

## 📚 Estrutura do Projeto

```
ecommerce/
├── app/
│   ├── Http/Controllers/     # Controllers REST API
│   ├── Models/              # Product, Order, Customer, etc.
│   └── Services/            # BlingService, integrations
├── database/
│   └── migrations/          # Schema do banco
├── routes/
│   ├── api.php             # Rotas da API REST
│   └── web.php             # Rotas web (admin, se houver)
├── compose.yaml            # Configuração Docker
└── .env                    # Variáveis de ambiente
```

## 🛠️ Desenvolvimento

### Models e Migrations

Criar modelos com migrations:

```powershell
.\vendor\bin\sail artisan make:model Product -m
.\vendor\bin\sail artisan make:model Customer -m
.\vendor\bin\sail artisan make:model Order -m
.\vendor\bin\sail artisan make:model OrderItem -m
```

### Controllers API

```powershell
.\vendor\bin\sail artisan make:controller Api/ProductController --api
.\vendor\bin\sail artisan make:controller Api/CartController
.\vendor\bin\sail artisan make:controller Api/CheckoutController
```

### Jobs e Queues

Para sincronização assíncrona com Bling:

```powershell
# Configurar queue driver no .env
QUEUE_CONNECTION=redis

# Criar jobs
.\vendor\bin\sail artisan make:job SyncProductToBling
.\vendor\bin\sail artisan make:job ProcessOrder

# Rodar queue worker
.\vendor\bin\sail artisan queue:work
```

## 🔒 Segurança

- Nunca comite o arquivo `.env` (já está no `.gitignore`)
- Use variáveis de ambiente para credenciais sensíveis
- Configure CORS adequadamente para aceitar requests do WordPress
- Use Sanctum para autenticação de API

## 📝 Git

```powershell
git init
git add .
git commit -m "chore: initial Laravel + Sail setup"
git remote add origin <seu-repositorio>
git push -u origin main
```

## 🐛 Troubleshooting

### Erro: "Port already allocated"

Outro serviço está usando a porta. Mude no `.env`:

```env
APP_PORT=8080
```

### Containers não sobem

```powershell
# Rebuild dos containers
.\vendor\bin\sail build --no-cache
.\vendor\bin\sail up -d
```

### Performance lenta

1. Verifique se Docker Desktop está usando WSL2 (não Hyper-V)
2. Aumente recursos do Docker (Settings → Resources)
3. Considere usar cache do Composer:

```powershell
.\vendor\bin\sail composer install --prefer-dist --optimize-autoloader
```

### Warnings de "Ambiguous class resolution"

São avisos sobre classes duplicadas no vendor. Não afetam o funcionamento. Para ocultar, adicione ao `composer.json`:

```json
"autoload": {
    "psr-4": {
        "App\\": "app/",
        "Database\\Factories\\": "database/factories/",
        "Database\\Seeders\\": "database/seeders/"
    },
    "exclude-from-classmap": [
        "vendor/league/flysystem-local/*",
        "vendor/laravel/pint/app/Providers/*"
    ]
}
```

Depois rode:

```powershell
.\vendor\bin\sail composer dump-autoload -o
```

## 📞 Suporte

- Laravel: https://laravel.com/docs
- Laravel Sail: https://laravel.com/docs/sail
- Bling API: https://developer.bling.com.br/

---

**Última atualização**: Novembro 2025
