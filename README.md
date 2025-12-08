# E-commerce Laravel + Bling ERP

Backend API do projeto Rodust E-commerce integrado com Bling ERP, Mercado Pago e Melhor Envio.

> **📚 Documentação completa:** [`/docs/`](../docs/)

---

## 🚀 Quick Start

```powershell
cd M:\Websites\rodust.com.br\ecommerce
.\docker-up.ps1
```

**Acessar API:** http://localhost:8000

**Novo no projeto?** Veja o [Guia Rápido do Usuário](../GUIA-RAPIDO-USUARIO.md)

---

## 📚 Documentação

Toda documentação foi centralizada em [`/docs/`](../docs/):

- 🚀 [Setup e Instalação](../docs/01-SETUP/)
- 🏗️ [Arquitetura](../docs/02-ARQUITETURA/)
- 🔌 [Integrações (Bling, MercadoPago, MelhorEnvio)](../docs/03-INTEGRACAO/)
- 💻 [Desenvolvimento (API, Helpers, Templates)](../docs/04-DESENVOLVIMENTO/)
- ✅ [Testes](../docs/05-TESTES/)
- 🚀 [Deploy](../docs/06-DEPLOY/)
- 📜 [Auditoria (Histórico Fases 1-6)](../docs/07-AUDITORIA/)
- 📖 [Referência (Changelog, Roadmap)](../docs/08-REFERENCIA/)

---

## 📋 Requisitos

- Docker Desktop com WSL2 habilitado
- Git
- PowerShell (Windows)

---

## 🚀 Configuração Inicial

### 1. Setup do Ambiente

O projeto está configurado para rodar com **Docker Compose**. Os arquivos ficam no SSD externo (`M:\`) mas são executados dentro de containers Linux para melhor performance e compatibilidade.

**⚠️ IMPORTANTE:** O Docker está configurado em `M:\Websites\rodust.com.br\docker\` (mesmo nível de `ecommerce/` e `wordpress/`).

### 2. Iniciar os Containers

No **PowerShell** (Windows), na raiz do projeto:

```powershell
# Subir os containers (usa o Docker em ../docker/)
.\docker-up.ps1

# Parar os containers
.\docker-down.ps1
```

Ou diretamente na pasta docker:

```powershell
cd M:\Websites\rodust.com.br\docker
docker compose up -d
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
.\artisan.ps1 migrate
```

### 4. Acessar a Aplicação

- **Aplicação Laravel**: http://localhost:8000
- **WordPress**: https://localhost:8443
- **MySQL**: `localhost:3307` (credenciais: `root` / `password`)
- **Redis**: `localhost:6379`

## 🌐 Webhooks (UltraHook)

Para receber webhooks do Mercado Pago durante o desenvolvimento, use o UltraHook:

```powershell
# 1. Instalar e configurar (primeira vez)
.\ultrahook-setup.ps1

# 2. Iniciar tunnel de webhooks
.\ultrahook-start.ps1

# 3. Parar tunnel (quando necessário)
.\ultrahook-stop.ps1
```

A URL pública será exibida quando o UltraHook iniciar. Configure-a no painel do Mercado Pago.

📖 **Documentação completa:** [`ULTRAHOOK-SETUP.md`](./ULTRAHOOK-SETUP.md)

## 🔧 Comandos Úteis do Docker

```powershell
# Iniciar containers (na raiz do projeto)
.\docker-up.ps1

# Parar containers
.\docker-down.ps1

# Ver logs
docker compose -f M:\Websites\rodust.com.br\docker\compose.yaml logs -f

# Executar comandos Artisan
.\artisan.ps1 [comando]
# Ou diretamente:
docker exec -it docker-laravel.test-1 php artisan [comando]

# Acessar shell do container Laravel
docker exec -it docker-laravel.test-1 bash

# Executar testes
docker exec -it docker-laravel.test-1 php artisan test
```

## 📦 Pacotes Instalados

Os pacotes essenciais já estão instalados via `composer.json`:
- `guzzlehttp/guzzle` - Cliente HTTP
- `laravel/sanctum` - Autenticação API
- `spatie/laravel-permission` - Permissões
- `mercadopago/dx-php` - SDK Mercado Pago

Para instalar novos pacotes:

```powershell
docker exec -it docker-laravel.test-1 composer require [pacote]
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

Laravel Sanctum já está configurado. Para publicar configurações:

```powershell
.\artisan.ps1 vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
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
# Use o Docker em ../docker/ ou configure localmente
```

## 🐳 Docker e Múltiplos Projetos

### Separação de Projetos

O Docker está configurado em `M:\Websites\rodust.com.br\docker\` e cria:
- **Rede própria**: `rodust-network`
- **Volumes próprios**: `rodust-mysql`, `rodust-redis`
- **Containers**: `docker-laravel.test-1`, `docker-laravel.queue-1`, `docker-wordpress-1`, etc.

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
├── docker-legacy/          # Arquivos Docker legados (não usados)
├── scripts/                # Scripts de desenvolvimento e manutenção
│   ├── debug/             # Scripts de debug
│   ├── maintenance/       # Scripts de manutenção
│   └── utils/             # Utilitários
└── .env                    # Variáveis de ambiente
```

## 🛠️ Desenvolvimento

### Models e Migrations

Criar modelos com migrations:

```powershell
.\artisan.ps1 make:model Product -m
.\artisan.ps1 make:model Customer -m
.\artisan.ps1 make:model Order -m
.\artisan.ps1 make:model OrderItem -m
```

### Controllers API

```powershell
.\artisan.ps1 make:controller Api/ProductController --api
.\artisan.ps1 make:controller Api/CartController
.\artisan.ps1 make:controller Api/CheckoutController
```

### Jobs e Queues

Para sincronização assíncrona com Bling:

```powershell
# Configurar queue driver no .env
QUEUE_CONNECTION=redis

# Criar jobs
.\artisan.ps1 make:job SyncProductToBling
.\artisan.ps1 make:job ProcessOrder

# Queue worker já roda automaticamente no container laravel.queue
# Para rodar manualmente:
docker exec -it docker-laravel.queue-1 php artisan queue:work
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
cd M:\Websites\rodust.com.br\docker
docker compose build --no-cache
docker compose up -d
```

### Performance lenta

1. Verifique se Docker Desktop está usando WSL2 (não Hyper-V)
2. Aumente recursos do Docker (Settings → Resources)
3. Considere usar cache do Composer:

```powershell
docker exec -it docker-laravel.test-1 composer install --prefer-dist --optimize-autoloader
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
docker exec -it docker-laravel.test-1 composer dump-autoload -o
```

## 📞 Suporte

- Laravel: https://laravel.com/docs
- Laravel Sail: https://laravel.com/docs/sail
- Bling API: https://developer.bling.com.br/

---

**Última atualização**: Novembro 2025
