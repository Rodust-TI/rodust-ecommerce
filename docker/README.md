# 🚀 Migração WordPress: XAMPP → Docker

## ⚡ Início Rápido (3 comandos)

### 1. Identificar caminho do WordPress no XAMPP

Abra o PowerShell e localize onde está seu WordPress:

```powershell
# Exemplos comuns:
# C:\xampp\htdocs\wordpress
# C:\xampp\htdocs\rodust
# C:\xampp\htdocs

# Confirme que existe wp-config.php nesse caminho
Test-Path "C:\xampp\htdocs\SEU_CAMINHO\wp-config.php"
# Deve retornar: True
```

### 2. Executar migração automática

```powershell
# Navegue até o projeto Laravel
cd M:\Websites\rodust.com.br\ecommerce

# Execute o script (SUBSTITUA pelo seu caminho)
.\docker\scripts\migrate-xampp-to-docker.ps1 -XamppWordPressPath "C:\xampp\htdocs\wordpress"
```

**Aguarde 2-5 minutos** enquanto o script:
- ✅ Cria backup
- ✅ Exporta banco do XAMPP
- ✅ Copia arquivos
- ✅ Inicia Docker
- ✅ Importa banco
- ✅ Atualiza URLs

### 3. Testar WordPress

```powershell
# Abrir no navegador
start http://localhost:8080
```

Se o site aparecer, **migração concluída!** 🎉

---

## 🔐 Configurar Application Password (Obrigatório)

### Por quê?

A senha permite o Laravel autenticar com segurança no WordPress via API REST para sincronizar produtos.

### Como criar

1. **Acesse HTTPS** (importante!): https://localhost:8443/wp-admin
   - Aceite o aviso de segurança do certificado self-signed
   
2. Faça login no WordPress

3. Vá em: **Usuários → Perfil**

4. Role até **"Application Passwords"**

5. Digite o nome: **"Laravel API"**

6. Clique **"Add New Application Password"**

7. **Copie a senha** gerada (formato: `xxxx xxxx xxxx xxxx xxxx xxxx`)

### Já configurado no .env! ✅

A senha que você gerou (`nuNp Daev 6Dmr jZd3 xkxq RaM0`) já está configurada no `.env`:

```env
WORDPRESS_URL=https://localhost:8443
WORDPRESS_API_USER=admin
WORDPRESS_API_PASSWORD=nuNp Daev 6Dmr jZd3 xkxq RaM0
```

**⚠️ Se você recriar a senha, atualize o `.env`!**

---

## 🧪 Testar Sincronização Laravel → WordPress

### Terminal 1: Iniciar Queue Worker

```powershell
docker compose exec laravel.test php artisan queue:work
```

Deixe este terminal aberto para ver os jobs processando.

### Terminal 2: Disparar Sincronização

```powershell
# Sincronizar todos os produtos
curl -X POST http://localhost:8000/api/products/sync-to-wordpress

# Ou sincronizar 1 produto específico (substitua {id})
curl -X POST http://localhost:8000/api/products/1/sync-to-wordpress
```

### Resultado Esperado

**Terminal 2 (curl):**
```json
{
  "success": true,
  "message": "2 produtos enfileirados para sincronização",
  "queued": 2,
  "estimated_time": "1 segundos"
}
```

**Terminal 1 (queue:work):**
```
[2025-11-26 14:30:15][ABC] Processing: App\Jobs\SyncProductToWordPress
[2025-11-26 14:30:16][ABC] Processed:  App\Jobs\SyncProductToWordPress
[2025-11-26 14:30:17][DEF] Processing: App\Jobs\SyncProductToWordPress
[2025-11-26 14:30:18][DEF] Processed:  App\Jobs\SyncProductToWordPress
```

### Verificar no WordPress

1. Acesse: http://localhost:8080/wp-admin/edit.php?post_type=rodust_product
2. Verifique que os posts foram criados
3. Cada post deve ter:
   - ✅ Título do produto
   - ✅ Slug/permalink
   - ✅ Meta field `_laravel_product_id`
   - ✅ Taxonomia `product_brand` (se tiver marca)

---

## 📋 Comandos Úteis

### Gerenciar Docker

```powershell
# Ver status
docker compose ps

# Parar tudo
docker compose down

# Iniciar novamente
docker compose up -d

# Ver logs em tempo real
docker compose logs -f wordpress
```

### Acessar URLs

- **Laravel API**: http://localhost:8000
- **WordPress HTTP**: http://localhost:8080
- **WordPress HTTPS**: https://localhost:8443
- **WordPress Admin**: http://localhost:8080/wp-admin

### Backup Rápido

```powershell
# Banco WordPress
docker compose exec mysql mysqldump -uroot -ppassword wordpress > backup.sql

# Arquivos WordPress
Compress-Archive -Path ..\wordpress -DestinationPath wordpress_backup.zip
```

---

## 🐛 Problemas Comuns

### Erro: "Porta 8080 já está em uso"

**Solução:** Pare o Apache do XAMPP

```powershell
# Parar todos os serviços do XAMPP
C:\xampp\xampp_stop.exe
```

### Erro: "Application Password não funciona"

**Causa:** Você está usando HTTP em vez de HTTPS

**Solução:** Use `https://localhost:8443` (não `http://localhost:8080`)

### Erro 500 no WordPress

**Solução:** Verificar permissões

```powershell
docker compose exec wordpress chown -R www-data:www-data /var/www/html
```

### URLs erradas no site

**Solução:** Atualizar no banco

```sql
docker compose exec mysql mysql -uroot -ppassword -D wordpress -e "
UPDATE wp_options SET option_value='http://localhost:8080' WHERE option_name='siteurl';
UPDATE wp_options SET option_value='http://localhost:8080' WHERE option_name='home';
"
```

---

## 📚 Documentação Completa

Para entender melhor como tudo funciona:

- 📖 [DOCKER_WORDPRESS.md](DOCKER_WORDPRESS.md) - Guia completo, arquitetura, troubleshooting
- 📖 [ARQUITETURA_HIBRIDA.md](ARQUITETURA_HIBRIDA.md) - Como funciona Laravel + WordPress
- 📖 [IMPLEMENTACAO_TEMPLATES.md](IMPLEMENTACAO_TEMPLATES.md) - Templates WordPress com API

---

## ✅ Checklist

- [ ] Docker Desktop instalado e rodando
- [ ] Script de migração executado
- [ ] WordPress acessível em http://localhost:8080
- [ ] Login no wp-admin funciona
- [ ] Application Password criado
- [ ] `.env` configurado com a senha
- [ ] Sincronização testada (queue:work + curl)
- [ ] Posts criados no WordPress

---

## 🎉 Pronto para Produção

Quando for fazer deploy:

1. **Altere o `.env`:**
   ```env
   WORDPRESS_URL=https://rodust.com.br
   WORDPRESS_API_USER=seu_usuario
   WORDPRESS_API_PASSWORD=nova_senha_de_producao
   ```

2. **Crie nova Application Password no servidor**

3. **Aponte o Laravel para o domínio real**

---

**Dúvidas?** Consulte [DOCKER_WORDPRESS.md](DOCKER_WORDPRESS.md) ou abra uma issue! 🚀
