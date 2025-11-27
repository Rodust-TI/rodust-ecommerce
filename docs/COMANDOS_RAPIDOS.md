# ⚡ Comandos Prontos - Copiar e Colar

## 🎯 MIGRAÇÃO (Escolha uma opção)

### Opção 1: Migração Automática Completa

```powershell
# Navegue até o projeto
cd M:\Websites\rodust.com.br\ecommerce

# Execute (SUBSTITUA o caminho pelo seu WordPress)
.\docker\scripts\migrate-xampp-to-docker.ps1 -XamppWordPressPath "C:\xampp\htdocs\wordpress"
```

### Opção 2: Migração Simplificada (Manual)

```powershell
cd M:\Websites\rodust.com.br\ecommerce
.\docker\scripts\migrate-simple.ps1
```

---

## 🐳 GERENCIAR DOCKER

### Iniciar containers
```powershell
docker compose up -d
```

### Parar containers
```powershell
docker compose down
```

### Ver status
```powershell
docker compose ps
```

### Ver logs em tempo real
```powershell
docker compose logs -f
```

### Ver logs de um serviço específico
```powershell
docker compose logs -f wordpress
docker compose logs -f laravel.test
docker compose logs -f mysql
```

### Reiniciar um serviço
```powershell
docker compose restart wordpress
```

---

## 🔐 APPLICATION PASSWORD

### Criar no WordPress

1. Acesse: https://localhost:8443/wp-admin
2. Aceite o certificado self-signed (Chrome: Avançado → Prosseguir)
3. Login
4. Usuários → Perfil
5. Role até "Application Passwords"
6. Nome: "Laravel API"
7. Clique "Add New Application Password"
8. **Copie a senha gerada**

### Testar autenticação

```powershell
# Substitua pela sua senha
curl -u "admin:xxxx xxxx xxxx xxxx xxxx xxxx" https://localhost:8443/wp-json/wp/v2/posts
```

Se retornar JSON, está funcionando! ✅

---

## 🧪 TESTAR SINCRONIZAÇÃO

### Terminal 1: Queue Worker

```powershell
docker compose exec laravel.test php artisan queue:work
```

**Deixe este terminal aberto!**

### Terminal 2: Disparar Sync

```powershell
# Sincronizar todos os produtos
curl -X POST http://localhost:8000/api/products/sync-to-wordpress

# Ou apenas 1 produto (substitua o ID)
curl -X POST http://localhost:8000/api/products/1/sync-to-wordpress
```

### Verificar no WordPress

```powershell
# Abrir admin
start http://localhost:8080/wp-admin/edit.php?post_type=rodust_product
```

---

## 💾 BANCO DE DADOS

### Exportar banco WordPress

```powershell
docker compose exec mysql mysqldump -uroot -ppassword wordpress > backup_wordpress.sql
```

### Importar banco WordPress

```powershell
Get-Content backup_wordpress.sql | docker compose exec -T mysql mysql -uroot -ppassword wordpress
```

### Atualizar URLs no banco

```powershell
docker compose exec mysql mysql -uroot -ppassword -D wordpress -e "
UPDATE wp_options SET option_value='http://localhost:8080' WHERE option_name='siteurl';
UPDATE wp_options SET option_value='http://localhost:8080' WHERE option_name='home';
UPDATE wp_posts SET guid = REPLACE(guid, 'http://localhost/wordpress', 'http://localhost:8080');
UPDATE wp_posts SET post_content = REPLACE(post_content, 'http://localhost/wordpress', 'http://localhost:8080');
"
```

### Acessar MySQL diretamente

```powershell
docker compose exec mysql mysql -uroot -ppassword
```

Depois:
```sql
SHOW DATABASES;
USE wordpress;
SHOW TABLES;
SELECT * FROM wp_options WHERE option_name IN ('siteurl', 'home');
```

---

## 🔧 COMANDOS LARAVEL

### Rodar migrations

```powershell
docker compose exec laravel.test php artisan migrate
```

### Limpar cache

```powershell
docker compose exec laravel.test php artisan cache:clear
docker compose exec laravel.test php artisan config:clear
docker compose exec laravel.test php artisan route:clear
```

### Listar rotas

```powershell
docker compose exec laravel.test php artisan route:list
```

### Entrar no container

```powershell
docker compose exec laravel.test bash
```

---

## 🌐 ACESSAR SERVIÇOS

### Abrir no navegador

```powershell
# Laravel
start http://localhost:8000

# WordPress (HTTP)
start http://localhost:8080

# WordPress Admin
start http://localhost:8080/wp-admin

# WordPress (HTTPS - para Application Password)
start https://localhost:8443
```

---

## 🐛 TROUBLESHOOTING

### Problema: Porta 8080 em uso

```powershell
# Parar Apache do XAMPP
C:\xampp\xampp_stop.exe

# Verificar o que está usando a porta
netstat -ano | findstr "8080"
```

### Problema: WordPress erro 500

```powershell
# Corrigir permissões
docker compose exec wordpress chown -R www-data:www-data /var/www/html

# Ver logs de erro
docker compose logs wordpress
```

### Problema: Containers não iniciam

```powershell
# Ver o erro
docker compose up

# Limpar tudo e recomeçar
docker compose down -v
docker compose up -d
```

### Problema: MySQL não aceita conexão

```powershell
# Reiniciar MySQL
docker compose restart mysql

# Ver logs
docker compose logs mysql

# Verificar se o banco existe
docker compose exec mysql mysql -uroot -ppassword -e "SHOW DATABASES;"
```

---

## 📦 BACKUP COMPLETO

### Criar backup de tudo

```powershell
# Criar pasta de backup
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$backupDir = "backups\backup_$timestamp"
New-Item -ItemType Directory -Path $backupDir -Force

# Backup banco Laravel
docker compose exec mysql mysqldump -uroot -ppassword laravel > "$backupDir\laravel.sql"

# Backup banco WordPress
docker compose exec mysql mysqldump -uroot -ppassword wordpress > "$backupDir\wordpress.sql"

# Backup arquivos WordPress
Compress-Archive -Path ..\wordpress -DestinationPath "$backupDir\wordpress_files.zip"

# Backup arquivos Laravel
Compress-Archive -Path .\* -DestinationPath "$backupDir\laravel_files.zip" -Exclude node_modules,vendor,storage

Write-Host "✅ Backup completo em: $backupDir" -ForegroundColor Green
```

---

## 🧹 LIMPEZA

### Limpar volumes e recomeçar do zero

```powershell
# ⚠️ CUIDADO: Isso apaga TODOS os dados dos containers!

# Parar tudo
docker compose down -v

# Remover volumes
docker volume rm ecommerce_sail-mysql ecommerce_sail-redis

# Iniciar limpo
docker compose up -d

# Aguardar 30s para MySQL inicializar
Start-Sleep -Seconds 30

# Reexecutar migrations
docker compose exec laravel.test php artisan migrate

# Reimportar banco WordPress (se tiver backup)
Get-Content backup_wordpress.sql | docker compose exec -T mysql mysql -uroot -ppassword wordpress
```

---

## 📊 MONITORAMENTO

### Ver uso de recursos

```powershell
# Listar containers com uso de CPU/RAM
docker stats
```

### Ver espaço em disco

```powershell
# Espaço usado pelo Docker
docker system df

# Limpar recursos não usados
docker system prune -a
```

---

## 🚀 DEPLOY (Quando for para produção)

### Configurar .env para produção

```env
# Laravel .env
APP_ENV=production
APP_DEBUG=false
WORDPRESS_URL=https://rodust.com.br
WORDPRESS_API_USER=admin
WORDPRESS_API_PASSWORD=senha_de_producao_aqui
```

### Otimizar Laravel

```powershell
docker compose exec laravel.test php artisan config:cache
docker compose exec laravel.test php artisan route:cache
docker compose exec laravel.test php artisan view:cache
```

---

## 💡 DICAS ÚTEIS

### Alias para comandos longos

Adicione ao seu PowerShell Profile (`$PROFILE`):

```powershell
# Atalhos Docker
function dcp { docker compose ps }
function dcu { docker compose up -d }
function dcd { docker compose down }
function dcl { docker compose logs -f $args }

# Atalhos Laravel
function artisan { docker compose exec laravel.test php artisan $args }
function tinker { docker compose exec laravel.test php artisan tinker }

# Atalhos WordPress
function wp-admin { start http://localhost:8080/wp-admin }
function wp-site { start http://localhost:8080 }
```

Depois use assim:
```powershell
dcu        # docker compose up -d
dcp        # docker compose ps
artisan migrate
wp-admin   # Abre admin no navegador
```

---

**📌 Salve este arquivo nos favoritos para referência rápida!**
