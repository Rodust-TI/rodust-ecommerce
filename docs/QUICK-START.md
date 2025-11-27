# ⚡ Guia Rápido - Rodust Ecommerce

> **⚠️ IMPORTANTE**: Este projeto usa **Laravel Sail** (Docker) e roda em **Windows com WSL2**.  
> **NUNCA** execute comandos Linux diretamente no PowerShell. Sempre use `docker compose exec laravel.test` antes dos comandos PHP/Artisan.

---

## 🎯 Ambiente de Desenvolvimento

**Sistema Operacional:** Windows com WSL2  
**Docker:** Docker Desktop for Windows  
**Laravel Sail:** Container já configurado com PHP 8.3, MySQL, Redis, Mailpit  
**Comandos:** PowerShell (não Bash/Linux direto)

### Por que usar `docker compose exec`?

- ✅ PHP, Composer, Artisan, MySQL já estão **DENTRO do container**
- ✅ Não precisa instalar PHP/Composer no Windows
- ✅ Garante mesmo ambiente em dev/produção
- ❌ NUNCA faça: `php artisan ...` ou `composer install` direto no PowerShell
- ✅ SEMPRE faça: `docker compose exec laravel.test php artisan ...`

---

## 🚀 Início Rápido (Primeira vez)

```powershell
# 1. Clone e entre na pasta
git clone https://github.com/Rodust-TI/rodust-ecommerce.git
cd rodust-ecommerce

# 2. Configure .env
cp .env.example .env
# Edite as variáveis BLING_*, WORDPRESS_*, DB_*

# 3. Suba os containers Docker (demora na primeira vez)
docker compose up -d

# 4. Instale dependências PHP (dentro do container)
docker compose exec laravel.test composer install

# 5. Gere a chave da aplicação
docker compose exec laravel.test php artisan key:generate

# 6. Execute migrations (cria tabelas no banco)
docker compose exec laravel.test php artisan migrate

# 7. Configure Bling (obrigatório para integração)
docker compose exec laravel.test php artisan bling:setup

# 8. Configure Melhor Envio (para cálculo de frete)
docker compose exec laravel.test php artisan melhorenvio:setup

# 9. Pronto! 🎉
# Acesse: http://localhost:8000
```

---

## 🔄 Rotina Diária (Toda vez que for trabalhar)

```powershell
# 1. Abrir PowerShell na pasta do projeto
cd M:\Websites\rodust.com.br\ecommerce

# 2. Iniciar containers (se não estiverem rodando)
docker compose up -d

# 3. Verificar se está tudo ok
docker compose ps

# 4. Trabalhar normalmente...

# 5. Ao terminar o dia (opcional - para economizar recursos)
docker compose down
```

### Comandos que você vai usar TODO DIA:

```powershell
# Ver logs em tempo real (útil para debug)
docker compose logs -f laravel.test

# Executar comandos Artisan
docker compose exec laravel.test php artisan COMANDO

# Acessar bash do container (se precisar explorar)
docker compose exec laravel.test bash

# Reiniciar container específico
docker compose restart laravel.test
```

---

## 🧪 Testes e Sincronizações

### Bling (Produtos e Clientes)

```powershell
# Sincronizar produtos do Bling → Laravel
docker compose exec laravel.test php artisan bling:sync-products

# Sincronizar produtos Laravel → WordPress
docker compose exec laravel.test php artisan products:sync-to-wordpress

# Testar sincronização de endereços (substitua 1 pelo ID do cliente)
docker compose exec laravel.test php artisan bling:test-address-sync 1

# Listar clientes com endereços
docker compose exec laravel.test php artisan customers:list-with-addresses
```

### Melhor Envio (Frete)

```powershell
# Configurar credenciais Melhor Envio
docker compose exec laravel.test php artisan melhorenvio:setup

# Iniciar ngrok (em terminal separado - para OAuth)
ngrok http 8000
# Copie a URL https://xxxxx.ngrok-free.app e configure no painel Melhor Envio

# Verificar configurações
docker compose exec laravel.test php artisan tinker
>>> \App\Models\MelhorEnvioSetting::first();
>>> exit
```

---

## 🔧 Manutenção e Troubleshooting

### Limpar Caches (fazer sempre após mudanças no .env ou configs)

```powershell
# Limpar TUDO de uma vez
docker compose exec laravel.test php artisan optimize:clear

# Ou limpar individualmente:
docker compose exec laravel.test php artisan cache:clear
docker compose exec laravel.test php artisan config:clear
docker compose exec laravel.test php artisan route:clear
docker compose exec laravel.test php artisan view:clear
```

### Problemas Comuns

#### Container não inicia / Erro ao subir

```powershell
# Parar tudo e reconstruir
docker compose down
docker compose up -d --build
```

#### Erro "Class not found" ou autoload

```powershell
docker compose exec laravel.test composer dump-autoload
docker compose exec laravel.test php artisan config:clear
```

#### Banco de dados não conecta

```powershell
# Verificar se MySQL está rodando
docker compose ps

# Reiniciar MySQL
docker compose restart mysql

# Ver logs do MySQL
docker compose logs mysql
```

#### Erro de permissão (Permission denied)

```powershell
docker compose exec laravel.test chmod -R 777 storage bootstrap/cache
```

#### Mudanças no código não aparecem

```powershell
# Limpar cache
docker compose exec laravel.test php artisan optimize:clear

# Reiniciar container
docker compose restart laravel.test
```

---

## 🔄 Queue Worker (Tarefas em Background)

Para processar jobs assíncronos (envio de emails, sync com Bling, etc):

```powershell
# Iniciar worker (deixe rodando em terminal separado)
docker compose exec laravel.test php artisan queue:work --tries=3 --timeout=300

# Se fizer mudanças no código, reinicie o worker:
docker compose exec laravel.test php artisan queue:restart
```

**Dica:** Abra um PowerShell só para o queue worker e deixe rodando.

---

## 📊 Monitoramento

```powershell
# Ver status de todos os containers
docker compose ps

# Ver uso de CPU/RAM
docker stats

# Ver últimas 100 linhas do log Laravel
docker compose exec laravel.test tail -100 storage/logs/laravel.log

# Ver apenas erros
docker compose exec laravel.test tail -100 storage/logs/laravel.log | Select-String "ERROR"

# Ver rotas da API
docker compose exec laravel.test php artisan route:list --path=api

# Verificar filas (jobs pendentes)
docker compose exec laravel.test php artisan queue:work --once
```

---

## 🔐 Acessos Locais

- **API Laravel:** http://localhost:8000
- **Mailpit (emails de teste):** http://localhost:8025
- **WordPress:** http://localhost (configurar separadamente)
- **MySQL (via cliente):**
  - Host: `localhost`
  - Port: `3306`
  - User: `sail`
  - Password: `password`
  - Database: `ecommerce`

---

## 📝 Git (Controle de Versão)

```powershell
# Ver status das mudanças
git status

# Adicionar arquivos modificados
git add .

# Commit com mensagem
git commit -m "Descrição das mudanças"

# Enviar para GitHub
git push origin main

# Atualizar com mudanças remotas
git pull origin main

# Ver histórico
git log --oneline
```

---

## 🔍 Comandos Úteis para Debug

```powershell
# Entrar no Tinker (console interativo PHP)
docker compose exec laravel.test php artisan tinker
>>> $customer = \App\Models\Customer::first();
>>> $customer->addresses;
>>> exit

# Ver variáveis de ambiente
docker compose exec laravel.test php artisan env

# Testar conexão com banco
docker compose exec laravel.test php artisan migrate:status

# Ver informações do PHP
docker compose exec laravel.test php -v
docker compose exec laravel.test php -i | Select-String "memory_limit"

# Executar comando MySQL diretamente
docker compose exec mysql mysql -u sail -ppassword ecommerce
```

---

## 📦 Estrutura de Pastas (Principais)

```
ecommerce/
├── app/
│   ├── Http/Controllers/API/    # Controllers da API
│   ├── Models/                  # Models (Customer, Product, Order, etc)
│   ├── Services/                # Lógica de negócio (BlingService, MelhorEnvioService)
│   └── Console/Commands/        # Comandos Artisan personalizados
├── database/
│   └── migrations/              # Migrations (estrutura do banco)
├── routes/
│   └── api.php                  # Rotas da API
├── storage/
│   └── logs/laravel.log         # Logs da aplicação
├── docs/                        # Documentação
│   ├── QUICK-START.md          # Este arquivo
│   ├── MELHOR-ENVIO.md         # Doc Melhor Envio
│   └── ADDRESS-SYSTEM.md       # Doc sistema de endereços
└── docker-compose.yml           # Configuração Docker
```

---

## 🚨 Regras de Ouro (NUNCA ESQUEÇA)

1. ✅ **SEMPRE** use `docker compose exec laravel.test` antes de comandos PHP
2. ❌ **NUNCA** execute `php artisan` diretamente no PowerShell
3. ❌ **NUNCA** execute `composer install` diretamente no PowerShell
4. ✅ **SEMPRE** limpe cache após mudanças em `.env`: `php artisan config:clear`
5. ✅ **SEMPRE** reinicie o queue worker após mudanças no código
6. ✅ **SEMPRE** verifique os logs quando algo der errado: `docker compose logs -f laravel.test`
7. ✅ Use **ngrok** para testar OAuth do Melhor Envio (não funciona com localhost puro)

---

## 📚 Links Úteis

- **Laravel Sail Docs:** https://laravel.com/docs/11.x/sail
- **Docker Desktop:** https://www.docker.com/products/docker-desktop/
- **ngrok:** https://ngrok.com/download
- **API Bling:** https://developer.bling.com.br/
- **API Melhor Envio:** https://docs.melhorenvio.com.br/

---

**💡 Dica Final:** Salve este arquivo nos favoritos e consulte sempre que tiver dúvida sobre comandos! 🚀
