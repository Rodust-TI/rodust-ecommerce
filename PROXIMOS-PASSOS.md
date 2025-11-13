# 🚀 Guia Rápido - Próximos Passos

## Status Atual ✅

- ✅ Laravel instalado
- ✅ Sail configurado (MySQL + Redis)
- ✅ README completo criado
- ⏳ Build da imagem Docker em andamento

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
$env:WWWGROUP="1000"
docker compose up -d

# Rodar migrations
docker compose exec laravel.test php artisan migrate

# Acessar shell do container
docker compose exec laravel.test bash
```

### Opção C: Usar WSL Diretamente (Recomendado)

```bash
# Abrir WSL Ubuntu
wsl

# Navegar para o projeto
cd /mnt/m/Websites/rodust.com.br/ecommerce

# Subir containers
./vendor/bin/sail up -d

# Rodar migrations
./vendor/bin/sail artisan migrate
```

## 🎯 Sequência Recomendada para Hoje

1. **Aguardar build terminar** (já está rodando)
2. **Subir containers:**
   ```bash
   # No WSL
   wsl
   cd /mnt/m/Websites/rodust.com.br/ecommerce
   ./vendor/bin/sail up -d
   ```
3. **Rodar migrations:**
   ```bash
   ./vendor/bin/sail artisan migrate
   ```
4. **Acessar http://localhost** no navegador
5. **Instalar pacotes:**
   ```bash
   ./vendor/bin/sail composer require guzzlehttp/guzzle laravel/sanctum spatie/laravel-permission
   ```

## 📚 Documentação Completa

Tudo está documentado no **`README.md`** que criei:
- Como usar Sail
- Arquitetura WordPress + Laravel
- Integração com Bling
- Troubleshooting completo
- Respostas sobre Docker e SSD

## ❓ Dúvidas Frequentes

**Q: Posso rodar este projeto e o outro ao mesmo tempo?**  
A: Sim, mas mude as portas no `.env` deste projeto:
```env
APP_PORT=8080
FORWARD_DB_PORT=3307
```

**Q: Como faço backup do projeto para levar em outro PC?**  
A: Apenas copie a pasta `M:\Websites\rodust.com.br\ecommerce` (sem `vendor/` e `node_modules/`). No outro PC rode `composer install`.

**Q: Preciso instalar PHP/MySQL/Redis no Windows?**  
A: NÃO! Tudo roda dentro dos containers Docker.

## 🆘 Se Algo Der Errado

```powershell
# Parar tudo e recomeçar
cd 'M:\Websites\rodust.com.br\ecommerce'
docker compose down
docker compose build --no-cache
docker compose up -d
```

---

**Próximo Passo:** Abrir terminal WSL e rodar `./vendor/bin/sail up -d` 🚀
