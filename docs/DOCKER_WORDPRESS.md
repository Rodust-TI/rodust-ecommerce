# 🐳 WordPress no Docker - Guia Completo

## 📋 Índice

1. [Por que Docker?](#por-que-docker)
2. [Arquitetura](#arquitetura)
3. [Migração do XAMPP](#migração-do-xampp)
4. [Comandos Úteis](#comandos-úteis)
5. [Troubleshooting](#troubleshooting)
6. [Usar em Outros Projetos](#usar-em-outros-projetos)

---

## 🎯 Por que Docker?

### Vantagens

✅ **Ambiente Reproduzível**: Mesma configuração em qualquer computador  
✅ **HTTPS Local**: Necessário para Application Passwords do WordPress  
✅ **Isolamento**: Não conflita com outras instalações (XAMPP, WAMP, etc)  
✅ **Portabilidade**: Fácil compartilhar com equipe  
✅ **Produção-like**: Ambiente local similar ao servidor  
✅ **Versionamento**: docker-compose.yml no Git = todos usam mesma versão  

### Desvantagens

⚠️ **Curva de aprendizado**: Precisa entender conceitos básicos de Docker  
⚠️ **Recursos**: Usa mais RAM que instalação nativa (mas é configurável)  
⚠️ **Primeira vez**: Setup inicial pode levar 15-30 minutos  

---

## 🏗️ Arquitetura

```
┌─────────────────────────────────────────────────────┐
│                   SEU COMPUTADOR                    │
│                                                     │
│  ┌─────────────┐  ┌─────────────┐  ┌────────────┐ │
│  │   Laravel   │  │  WordPress  │  │   MySQL    │ │
│  │             │  │             │  │            │ │
│  │  Port 8000  │  │  Port 8080  │  │ Port 3307  │ │
│  │    (HTTP)   │  │  Port 8443  │  │  (interno) │ │
│  │             │  │   (HTTPS)   │  │            │ │
│  └──────┬──────┘  └──────┬──────┘  └──────┬─────┘ │
│         │                │                │       │
│         └────────────────┴────────────────┘       │
│                     Docker Network                 │
│                      (sail)                        │
└─────────────────────────────────────────────────────┘
```

### Serviços

| Serviço | Porta | Acesso | Descrição |
|---------|-------|--------|-----------|
| **Laravel** | 8000 | http://localhost:8000 | API e Backend Laravel |
| **WordPress** | 8080 | http://localhost:8080 | Site WordPress (HTTP) |
| **WordPress SSL** | 8443 | https://localhost:8443 | Site WordPress (HTTPS) |
| **MySQL** | 3307 | Interno (Docker) | Banco de dados compartilhado |
| **Redis** | 6379 | Interno (Docker) | Cache e Filas |

### Bancos de Dados

O MySQL compartilhado tem **2 bancos de dados**:

1. **`laravel`** - Produtos, pedidos, clientes, etc (fonte de dados)
2. **`wordpress`** - Posts vazios para SEO (permalinks, taxonomias)

---

## 🚀 Migração do XAMPP

### Pré-requisitos

- [x] Docker Desktop instalado e rodando
- [x] XAMPP com WordPress funcionando
- [x] PowerShell 5.1+ (vem no Windows)

### Passo a Passo

#### 1. Identifique o caminho do WordPress no XAMPP

Exemplos comuns:
- `C:\xampp\htdocs\wordpress`
- `C:\xampp\htdocs\rodust`
- `C:\xampp\htdocs` (se WordPress está na raiz)

Para confirmar, verifique se existe o arquivo `wp-config.php` nesse caminho.

#### 2. Execute o script de migração

```powershell
# Navegue até a pasta do projeto Laravel
cd M:\Websites\rodust.com.br\ecommerce

# Execute o script (SUBSTITUA O CAMINHO PELO SEU)
.\docker\scripts\migrate-xampp-to-docker.ps1 -XamppWordPressPath "C:\xampp\htdocs\wordpress"
```

**Parâmetros opcionais:**

```powershell
# Se o MySQL do XAMPP tiver senha
.\docker\scripts\migrate-xampp-to-docker.ps1 `
    -XamppWordPressPath "C:\xampp\htdocs\wordpress" `
    -XamppMySQLPassword "sua_senha"

# Se o banco tiver nome diferente
.\docker\scripts\migrate-xampp-to-docker.ps1 `
    -XamppWordPressPath "C:\xampp\htdocs\wordpress" `
    -WordPressDBName "rodust_db"
```

#### 3. Aguarde a migração

O script irá:

1. ✅ Validar ambiente (Docker, XAMPP, arquivos)
2. ✅ Criar backup do WordPress (arquivos + banco)
3. ✅ Exportar banco de dados do XAMPP
4. ✅ Copiar arquivos para `../wordpress`
5. ✅ Iniciar containers Docker
6. ✅ Importar banco no MySQL do Docker
7. ✅ Atualizar URLs no banco (localhost/wordpress → localhost:8080)

**Tempo estimado:** 2-5 minutos (depende do tamanho do banco)

#### 4. Verifique o resultado

Acesse: http://localhost:8080

Se aparecer o site, **migração concluída!** 🎉

---

## 🔐 Configurar Application Password

### Por que preciso disso?

A **Application Password** permite o Laravel autenticar com segurança na API REST do WordPress para criar/atualizar posts de produtos.

### Como criar

1. Acesse: https://localhost:8443/wp-admin (**use HTTPS!**)
2. **Aceite o aviso de segurança** do certificado self-signed:
   - Chrome/Edge: Clique "Avançado" → "Prosseguir para localhost (não seguro)"
   - Firefox: "Avançado" → "Aceitar risco e continuar"
3. Faça login no WordPress
4. Vá em: **Usuários → Perfil** (ou clique no seu nome → Editar Perfil)
5. Role até a seção **"Application Passwords"**
   - Se não aparecer, adicione ao `wp-config.php`:
     ```php
     define('APPLICATION_PASSWORD_ENABLED', true);
     ```
6. Digite o nome: **"Laravel API"**
7. Clique em **"Add New Application Password"**
8. **Copie a senha gerada** (formato: `xxxx xxxx xxxx xxxx xxxx xxxx`)

### Configurar no Laravel

Edite o arquivo `.env`:

```env
# Adicione estas linhas (ou atualize se já existirem)
WORDPRESS_URL=https://localhost:8443
WORDPRESS_API_USER=admin
WORDPRESS_API_PASSWORD=xxxx xxxx xxxx xxxx xxxx xxxx
```

**⚠️ IMPORTANTE:**
- Use **HTTPS** (porta 8443)
- Substitua `admin` pelo seu usuário real
- Cole a senha **exatamente como foi gerada** (com espaços)

---

## 🛠️ Comandos Úteis

### Gerenciar Containers

```powershell
# Ver status dos containers
docker compose ps

# Iniciar containers
docker compose up -d

# Parar containers
docker compose down

# Ver logs em tempo real
docker compose logs -f

# Ver logs de um serviço específico
docker compose logs -f wordpress
docker compose logs -f laravel.test

# Reiniciar um serviço
docker compose restart wordpress

# Reconstruir containers (após mudar compose.yaml)
docker compose up -d --build
```

### Acessar Containers

```powershell
# Entrar no container do Laravel (executar comandos Artisan)
docker compose exec laravel.test bash

# Entrar no container do WordPress
docker compose exec wordpress bash

# Entrar no MySQL
docker compose exec mysql mysql -uroot -ppassword
```

### Comandos Laravel no Docker

```powershell
# Rodar migrations
docker compose exec laravel.test php artisan migrate

# Rodar queue worker
docker compose exec laravel.test php artisan queue:work

# Limpar cache
docker compose exec laravel.test php artisan cache:clear

# Listar rotas
docker compose exec laravel.test php artisan route:list
```

### Backup e Restore

#### Backup do banco WordPress

```powershell
# Exportar banco
docker compose exec mysql mysqldump -uroot -ppassword wordpress > backup_wordpress.sql

# Restaurar banco
Get-Content backup_wordpress.sql | docker compose exec -T mysql mysql -uroot -ppassword wordpress
```

#### Backup dos arquivos WordPress

```powershell
# Criar ZIP
Compress-Archive -Path ..\wordpress -DestinationPath wordpress_backup.zip

# Restaurar ZIP
Expand-Archive -Path wordpress_backup.zip -DestinationPath ..\ -Force
```

---

## 🐛 Troubleshooting

### Problema: Containers não iniciam

**Sintoma:** `docker compose up -d` falha ou containers ficam reiniciando

**Soluções:**

1. Verificar se portas estão livres:
   ```powershell
   # Verificar se algum processo está usando as portas
   netstat -ano | findstr "8000 8080 8443 3307"
   ```

2. Parar XAMPP (se ainda estiver rodando):
   - Apache do XAMPP usa porta 80 (conflita com Laravel)
   - MySQL do XAMPP usa porta 3306 (diferente do Docker, mas pode confundir)

3. Aumentar memória do Docker:
   - Docker Desktop → Settings → Resources → Memory: 4GB+

4. Ver logs de erro:
   ```powershell
   docker compose logs
   ```

### Problema: WordPress não carrega (erro 500)

**Sintoma:** http://localhost:8080 mostra erro 500 ou página em branco

**Soluções:**

1. Verificar permissões dos arquivos:
   ```powershell
   docker compose exec wordpress chown -R www-data:www-data /var/www/html
   ```

2. Verificar banco de dados:
   ```powershell
   docker compose exec mysql mysql -uroot -ppassword -e "SHOW DATABASES;"
   ```
   - Deve listar `wordpress` e `laravel`

3. Verificar wp-config.php:
   - Abra `M:\Websites\rodust.com.br\wordpress\wp-config.php`
   - Confirme que as credenciais do banco estão corretas:
     ```php
     define('DB_NAME', 'wordpress');
     define('DB_USER', 'sail');
     define('DB_PASSWORD', 'password');
     define('DB_HOST', 'mysql:3306');
     ```

### Problema: Application Password não funciona

**Sintoma:** Erro 401 ou 403 ao tentar sincronizar produtos

**Soluções:**

1. **Usar HTTPS** (porta 8443):
   ```env
   WORDPRESS_URL=https://localhost:8443  # ✅ Correto
   WORDPRESS_URL=http://localhost:8080   # ❌ Não funciona
   ```

2. Verificar se Application Password está habilitado:
   ```php
   // wp-config.php
   define('APPLICATION_PASSWORD_ENABLED', true);
   ```

3. Testar autenticação manualmente:
   ```powershell
   curl -u "admin:xxxx xxxx xxxx xxxx xxxx xxxx" https://localhost:8443/wp-json/wp/v2/posts
   ```
   - Deve retornar JSON com lista de posts

4. Recriar Application Password:
   - Vá em wp-admin → Usuários → Perfil
   - Revogue a senha antiga
   - Crie nova senha

### Problema: URLs erradas no site

**Sintoma:** Links apontam para `localhost/wordpress` em vez de `localhost:8080`

**Solução:**

```sql
-- Acessar MySQL
docker compose exec mysql mysql -uroot -ppassword -D wordpress

-- Atualizar URLs
UPDATE wp_options SET option_value = 'http://localhost:8080' WHERE option_name = 'siteurl';
UPDATE wp_options SET option_value = 'http://localhost:8080' WHERE option_name = 'home';
```

Ou use o plugin **Better Search Replace** no wp-admin.

---

## 📦 Usar em Outros Projetos

### Estrutura Necessária

```
seu-projeto/
├── laravel/                 # Projeto Laravel (opcional)
├── wordpress/               # Arquivos do WordPress
├── docker/
│   ├── mysql/
│   │   └── init/
│   │       └── 01-create-databases.sql
│   ├── wordpress/
│   │   └── uploads.ini
│   └── scripts/
│       └── migrate-xampp-to-docker.ps1
└── compose.yaml             # Docker Compose
```

### Arquivos para Copiar

1. **`compose.yaml`** (seção do WordPress)
2. **`docker/mysql/init/01-create-databases.sql`**
3. **`docker/wordpress/uploads.ini`**
4. **`docker/scripts/migrate-xampp-to-docker.ps1`** (opcional)

### Configuração Mínima (Só WordPress)

Se você quer **apenas WordPress** (sem Laravel):

```yaml
# compose.yaml
services:
  wordpress:
    image: wordpress:latest
    ports:
      - "80:80"
      - "443:443"
    environment:
      WORDPRESS_DB_HOST: mysql:3306
      WORDPRESS_DB_USER: wordpress
      WORDPRESS_DB_PASSWORD: wordpress
      WORDPRESS_DB_NAME: wordpress
    volumes:
      - ./wordpress:/var/www/html
    networks:
      - wp-network

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: rootpass
      MYSQL_DATABASE: wordpress
      MYSQL_USER: wordpress
      MYSQL_PASSWORD: wordpress
    volumes:
      - wp-mysql:/var/lib/mysql
    networks:
      - wp-network

networks:
  wp-network:

volumes:
  wp-mysql:
```

**Uso:**

```powershell
# Iniciar
docker compose up -d

# Acessar
http://localhost

# Parar
docker compose down
```

### Template para Novos Projetos

Criei um template público no GitHub que você pode usar:

```powershell
# Clonar template
git clone https://github.com/seu-usuario/wordpress-docker-template meu-projeto
cd meu-projeto

# Iniciar
docker compose up -d

# Acessar
http://localhost
```

---

## 📚 Recursos Úteis

### Documentação Oficial

- [Docker Compose - WordPress](https://docs.docker.com/samples/wordpress/)
- [WordPress Docker Hub](https://hub.docker.com/_/wordpress)
- [Laravel Sail](https://laravel.com/docs/sail)

### Plugins WordPress Recomendados

- **WP-CLI**: Gerenciar WordPress via linha de comando
- **Better Search Replace**: Atualizar URLs no banco
- **Query Monitor**: Debug de queries SQL
- **Redis Object Cache**: Cache com Redis (já temos no Docker!)

### Ferramentas Úteis

- **TablePlus**: Cliente visual para MySQL (melhor que PHPMyAdmin)
- **Postman**: Testar API REST do WordPress
- **VS Code Extensions**:
  - Docker
  - PHP Intelephense
  - WordPress Snippets

---

## 🎓 Conceitos Docker (Para Iniciantes)

### O que é um Container?

Um **container** é como uma "máquina virtual leve" que:
- Roda um software isolado (WordPress, MySQL, etc)
- Compartilha o kernel do sistema operacional
- É descartável (pode parar/iniciar/recriar sem perder dados nos volumes)

### O que é um Volume?

Um **volume** é um espaço de armazenamento persistente:
- Arquivos ficam salvos mesmo se você recriar o container
- Exemplo: `sail-mysql` (dados do banco), `../wordpress` (arquivos do site)

### O que é uma Network?

Uma **network** permite containers conversarem entre si:
- Containers na mesma network podem se chamar pelo nome
- Exemplo: WordPress chama MySQL como `mysql:3306`

### Comandos Essenciais

```powershell
# Listar containers rodando
docker ps

# Listar todos os containers (incluindo parados)
docker ps -a

# Ver imagens baixadas
docker images

# Limpar recursos não usados (libera espaço)
docker system prune -a
```

---

## ✅ Checklist de Migração

Use este checklist para garantir que tudo está funcionando:

- [ ] Docker Desktop instalado e rodando
- [ ] Containers iniciados (`docker compose up -d`)
- [ ] WordPress acessível em http://localhost:8080
- [ ] WordPress HTTPS acessível em https://localhost:8443 (aceitar certificado)
- [ ] Login no wp-admin funciona
- [ ] Application Password criado
- [ ] Application Password configurado no `.env` do Laravel
- [ ] Banco `wordpress` existe no MySQL (`docker compose exec mysql mysql -uroot -ppassword -e "SHOW DATABASES;"`)
- [ ] Posts/páginas do XAMPP foram migrados
- [ ] Imagens carregam corretamente
- [ ] Plugins ativos funcionam
- [ ] URLs corretas (localhost:8080)

---

## 🎉 Conclusão

Agora você tem um **ambiente WordPress profissional** que:

✅ Funciona em qualquer computador com Docker  
✅ Suporta HTTPS para Application Passwords  
✅ É versionável no Git (docker-compose.yaml)  
✅ Facilita trabalho em equipe  
✅ É similar ao ambiente de produção  

**Próximos passos:**
1. Execute o script de migração
2. Crie Application Password
3. Configure `.env` do Laravel
4. Teste sincronização Laravel → WordPress

Se tiver dúvidas, consulte a seção [Troubleshooting](#troubleshooting) ou abra uma issue no repositório! 🚀

---

**Data:** 26 de Novembro de 2025  
**Autor:** GitHub Copilot (Claude Sonnet 4.5)  
**Versão:** 1.0
