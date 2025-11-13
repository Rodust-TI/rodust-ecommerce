# 🚀 Roadmap - Rodust Ecommerce

## ✅ Concluído

### Infraestrutura
- [x] Laravel 12.10.1 instalado com Docker Sail
- [x] MySQL 8.0 (porta 3307) + Redis configurados
- [x] WordPress instalado e conectado ao XAMPP
- [x] Porta Laravel alterada para 8000 (evitar conflito com XAMPP porta 80)

### Arquitetura ERP
- [x] ERPInterface (abstração genérica para qualquer ERP)
- [x] BlingV3Adapter (implementação Bling API v3 com OAuth2)
- [x] ERPServiceProvider (dependency injection)
- [x] BlingValidateCommand (validação homologação Bling)
- [x] Token refresh automático (30 dias)
- [x] Normalize/Denormalize para transformação de dados

### Database Schema
- [x] Tabela `products` (SKU, nome, preço, estoque, bling_id, last_bling_sync)
- [x] Tabela `customers` (nome, email, telefone, CPF/CNPJ, bling_id)
- [x] Tabela `orders` (cliente, total, status, payment_method, bling_id)
- [x] Tabela `order_items` (produto, quantidade, preço, desconto)
- [x] Campos de nota fiscal (invoice_number, invoice_key, invoice_issued_at)

### API REST
- [x] ProductController (CRUD produtos)
- [x] OrderController (criar/listar pedidos)
- [x] WebhookController (receber eventos do Bling)
- [x] Rotas públicas `/api/products` e `/api/orders`
- [x] Rotas admin protegidas com Sanctum
- [x] Webhook endpoint `/api/webhooks/bling`

### WordPress Plugin
- [x] Estrutura completa (16 arquivos)
- [x] Custom Post Type `rodust_product`
- [x] 4 Taxonomias (categoria, tag, marca, tipo de ferramenta)
- [x] API Client genérico
- [x] Settings page com teste de conexão
- [x] Documentação (README.md com 400+ linhas)
- [x] Conexão WordPress ↔ Laravel testada e funcionando

### Webhooks Bling
- [x] Handler para produtos (criar/atualizar/deletar)
- [x] Handler para estoques (atualizar saldo em tempo real)
- [x] Handler para pedidos (mudança de status)
- [x] Handler para NF-e/NFC-e (salvar dados da nota fiscal)
- [x] Logs detalhados de todos os eventos

---

## 🔄 Em Andamento

### Validação Bling
- [ ] Executar comando `php artisan bling:validate --token=TOKEN`
- [ ] Obter token OAuth2 do Bling
- [ ] Testar buscar produto já cadastrado no Bling
- [ ] Validar sincronização bidirecional

---

## 📋 Próximos Passos

### 1. Integração Bling (Prioridade: ALTA)
- [ ] Obter token OAuth2 via link de convite do Bling
- [ ] Configurar webhooks no painel Bling:
  - Alias: `rodust-ecommerce`
  - URL: `http://localhost:8000/api/webhooks/bling` (testes locais)
  - Ativar: produtos, estoques, pedidos, notasfiscais, nfce
- [ ] Criar comando para importar produtos existentes do Bling
- [ ] Testar fluxo completo: pedido no WP → Laravel → Bling
- [ ] Implementar sincronização de categorias do Bling

### 2. Segurança Webhooks (Prioridade: ALTA - PRÉ-PRODUÇÃO)
- [ ] Implementar validação de assinatura HMAC-SHA256 do Bling
- [ ] Adicionar whitelist de IPs do Bling
- [ ] Remover bypass de validação em ambiente local (linha 79 WebhookController)
- [ ] Adicionar rate limiting nos endpoints de webhook
- [ ] Log de tentativas de acesso não autorizadas

### 3. WordPress Frontend (Prioridade: ALTA)
- [ ] Implementar listagem de produtos (loop WordPress)
- [ ] Página de produto individual (single-rodust_product.php)
- [ ] Sistema de carrinho (WooCommerce-like):
  - Session/Cookie para armazenar itens
  - AJAX para adicionar/remover produtos
  - Exibir subtotal/total
- [ ] Checkout:
  - Formulário de dados do cliente
  - Seleção de endereço de entrega
  - Escolha de método de pagamento
  - Integração com gateway (PIX, cartão, boleto)
  - Enviar pedido para Laravel API
- [ ] Página "Meus Pedidos" (rastreamento)
- [ ] Filtros e busca de produtos
- [ ] Breadcrumbs e navegação

### 4. Sincronização Automática
- [ ] Job para sincronizar produtos Laravel → Bling (a cada X minutos)
- [ ] Job para sincronizar estoque Bling → Laravel (a cada X minutos)
- [ ] Command para sincronização manual: `php artisan sync:bling --products --stock`
- [ ] Tratamento de conflitos (último a atualizar vence)
- [ ] Fila de retry para sincronizações falhadas

### 5. Gestão de Estoque
- [ ] Validar estoque antes de finalizar pedido
- [ ] Reservar estoque ao criar pedido (não permitir overselling)
- [ ] Liberar estoque se pedido cancelado
- [ ] Alertas de estoque baixo (notificação admin)
- [ ] Histórico de movimentações de estoque

### 6. Pagamentos
- [ ] Integração com Mercado Pago (PIX, cartão, boleto)
- [ ] Ou: Integração com PagSeguro / PayPal
- [ ] Webhooks de confirmação de pagamento
- [ ] Atualizar status do pedido: pending → paid → processing
- [ ] Salvar transaction_id e método usado

### 7. Envio e Logística
- [ ] Integração com Correios (cálculo de frete)
- [ ] Ou: Melhor Envio / Frenet (cotação múltiplas transportadoras)
- [ ] Salvar código de rastreamento no pedido
- [ ] Enviar email com código de rastreamento ao cliente
- [ ] Atualizar status: paid → shipped → delivered

### 8. Emails Transacionais
- [ ] Email de confirmação de pedido
- [ ] Email de pagamento aprovado
- [ ] Email de pedido enviado (com rastreamento)
- [ ] Email de pedido entregue
- [ ] Email de pedido cancelado
- [ ] Templates HTML responsivos

### 9. Admin Dashboard (Laravel)
- [ ] Dashboard com métricas (vendas, pedidos, estoque)
- [ ] CRUD de produtos (interface visual)
- [ ] Gestão de pedidos (mudar status, cancelar, reembolsar)
- [ ] Relatórios de vendas (diário, mensal, anual)
- [ ] Logs de sincronização com Bling
- [ ] Gestão de clientes

### 10. SEO e Performance
- [ ] Meta tags dinâmicas (Yoast SEO ou similar)
- [ ] Schema.org markup para produtos
- [ ] Sitemap XML de produtos
- [ ] Cache de respostas da API (Redis)
- [ ] CDN para imagens de produtos
- [ ] Lazy loading de imagens
- [ ] Minificar CSS/JS

### 11. Testes
- [ ] Testes unitários (Models, Services)
- [ ] Testes de integração (API endpoints)
- [ ] Testes de webhook (simular eventos Bling)
- [ ] Testes E2E (checkout completo)
- [ ] CI/CD com GitHub Actions

### 12. Produção
- [ ] Migrar para servidor (VPS, AWS, DigitalOcean)
- [ ] Configurar SSL (Let's Encrypt)
- [ ] Atualizar BLING_REDIRECT_URI para URL real
- [ ] Configurar webhooks Bling com URL pública (https://rodust.com.br/api/webhooks/bling)
- [ ] Backup automático do banco de dados
- [ ] Monitoramento (Sentry, New Relic)
- [ ] Logs centralizados
- [ ] Firewall e proteção DDoS

### 13. Troca de ERP (Futuro Distante)
- [ ] Criar adapter para novo ERP (implementar ERPInterface)
- [ ] Atualizar ERPServiceProvider para usar novo adapter
- [ ] Migrar dados do Bling para novo ERP
- [ ] Testar todos os fluxos com novo ERP

---

## 🐛 Bugs Conhecidos
- Nenhum no momento

---

## 💡 Ideias Futuras
- [ ] Programa de fidelidade (pontos)
- [ ] Cupons de desconto
- [ ] Produtos relacionados / Cross-sell
- [ ] Avaliações de produtos
- [ ] Wishlist (lista de desejos)
- [ ] Comparador de produtos
- [ ] Multi-idioma (PT, EN, ES)
- [ ] Multi-moeda (BRL, USD, EUR)
- [ ] B2B: preços diferenciados para atacado
- [ ] Marketplace: múltiplos vendedores

---

## 📝 Notas Técnicas
- **Arquitetura**: Headless (Laravel API + WordPress Frontend)
- **Abstração ERP**: ERPInterface permite trocar Bling por outro ERP com mudança de 1 linha
- **Segurança**: Nunca armazenar credenciais Bling no WordPress (apenas no Laravel .env)
- **Sincronização**: Webhooks em tempo real + Jobs agendados (redundância)
- **Estoque**: Bling é source of truth, Laravel é cache local

---

## 🛠️ Comandos Úteis

```bash
# Iniciar containers
docker compose up -d

# Ver logs em tempo real
docker compose logs -f laravel.test

# Worker de filas (rodar em terminal separado)
docker compose exec laravel.test php artisan queue:work redis --tries=3

# Validar integração Bling
docker compose exec laravel.test php artisan bling:validate --token=SEU_TOKEN_AQUI

# Rodar migrations
docker compose exec laravel.test php artisan migrate

# Rollback última migration
docker compose exec laravel.test php artisan migrate:rollback

# Criar migration
docker compose exec laravel.test php artisan make:migration nome_da_migration

# Criar model com migration e controller
docker compose exec laravel.test php artisan make:model NomeModel -mc

# Limpar cache
docker compose exec laravel.test php artisan cache:clear
docker compose exec laravel.test php artisan config:clear
docker compose exec laravel.test php artisan route:clear

# Listar rotas
docker compose exec laravel.test php artisan route:list

# Acessar MySQL
docker compose exec mysql mysql -u sail -ppassword laravel

# Testar API
curl http://localhost:8000/api/products
curl http://localhost:8000/api/products/1

# Git
git status
git add .
git commit -m "mensagem"
git log --oneline
```

---

**Última atualização:** 2025-11-13
