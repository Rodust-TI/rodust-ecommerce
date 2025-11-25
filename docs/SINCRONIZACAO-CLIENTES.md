# Sincronização de Clientes com Bling

Documentação completa da sincronização automática de clientes entre o sistema e o Bling ERP.

## Visão Geral

Quando um cliente se cadastra no site e **confirma seu email**, ele é automaticamente enviado para o Bling ERP como "Cliente Ecommerce". A sincronização é **assíncrona** (via jobs) para não bloquear o fluxo do usuário.

## Fluxo de Sincronização

```
Cliente cadastra → Email enviado → Cliente confirma email → Job disparado → Enviado para Bling
```

### Detalhamento

1. **Cadastro**: Cliente preenche formulário no WordPress
2. **Email de Verificação**: Sistema envia email com link de confirmação
3. **Confirmação**: Cliente clica no link e email é verificado
4. **Trigger Automático**: `SyncCustomerToBling` job é disparado
5. **Processamento**: Job tenta enviar cliente para Bling (3 tentativas)
6. **Resultado**: Cliente salvo no Bling com tags "Ecommerce" e "Site"

## Componentes

### 1. Service: BlingCustomerService

**Arquivo**: `app/Services/BlingCustomerService.php`

Responsável pela comunicação com a API do Bling.

**Métodos principais**:

- `createCustomer(Customer $customer)`: Cria novo cliente no Bling
- `updateCustomer(Customer $customer)`: Atualiza cliente existente
- `searchCustomerByEmail(string $email)`: Busca cliente por email
- `prepareCustomerPayload(Customer $customer)`: Prepara dados no formato da API

**Payload enviado ao Bling**:

```php
[
    'nome' => 'Nome do Cliente',
    'codigo' => '123', // ID do nosso sistema
    'situacao' => 'A', // Ativo
    'numeroDocumento' => '12345678901', // CPF sem formatação
    'tipoPessoa' => 'F', // F = Física, J = Jurídica
    'email' => 'cliente@email.com',
    'celular' => '11999999999',
    'tipo' => 'C', // C = Cliente
    'endereco' => [
        'geral' => [
            'endereco' => 'Rua Exemplo',
            'numero' => '123',
            'bairro' => 'Centro',
            'cep' => '12345678',
            'municipio' => 'São Paulo',
            'uf' => 'SP',
        ]
    ],
    'dadosAdicionais' => [
        'observacoes' => 'Cliente Ecommerce - Cadastrado via site',
        'tags' => [
            ['tag' => 'Ecommerce'],
            ['tag' => 'Site']
        ]
    ]
]
```

### 2. Job: SyncCustomerToBling

**Arquivo**: `app/Jobs/SyncCustomerToBling.php`

Job assíncrono que envia cliente para o Bling.

**Configurações**:
- **Tentativas**: 3 (retry automático em caso de falha)
- **Backoff**: 60 segundos entre tentativas
- **Queue**: default

**Lógica**:

1. Verifica se cliente já tem `bling_id` (atualização ou criação)
2. Se não tem, busca no Bling por email para evitar duplicatas
3. Cria ou atualiza cliente no Bling
4. Salva `bling_id` e `bling_synced_at` no banco local
5. Registra logs detalhados de sucesso/falha

### 3. Campos na Tabela `customers`

```sql
bling_id VARCHAR(255) NULLABLE UNIQUE
bling_synced_at TIMESTAMP NULLABLE
```

- `bling_id`: ID do cliente no Bling (vazio até primeira sincronização)
- `bling_synced_at`: Data/hora da última sincronização bem-sucedida

### 4. Trigger no Controller

**Arquivo**: `app/Http/Controllers/Api/CustomerController.php`

Método `verifyEmail()` dispara o job após verificação:

```php
// Disparar sincronização com Bling (assíncrono)
SyncCustomerToBling::dispatch($customer);
```

## Configuração

### Requisitos

1. **Bling OAuth2 configurado** (.env):
   ```env
   BLING_CLIENT_ID=seu_client_id
   BLING_CLIENT_SECRET=seu_client_secret
   BLING_REDIRECT_URI=http://localhost:8000/bling/callback
   ```

2. **Access Token válido** no cache:
   - Obtido via OAuth2 flow
   - Armazenado em `bling_access_token` (Redis)
   - Renovado automaticamente quando expira

3. **Queue worker rodando**:
   ```bash
   php artisan queue:work
   ```

### Em Produção

Para processar jobs em produção, configure Supervisor:

**`/etc/supervisor/conf.d/laravel-worker.conf`**:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /caminho/para/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/caminho/para/logs/worker.log
```

## Testando

### 1. Verificar se o job está funcionando

```bash
# Acessar container
docker exec -it ecommerce-laravel.test-1 bash

# Rodar queue worker
php artisan queue:work --once
```

### 2. Testar sincronização manual

```bash
php artisan tinker

# Buscar um cliente
$customer = App\Models\Customer::first();

# Disparar job manualmente
App\Jobs\SyncCustomerToBling::dispatch($customer);

# Verificar se foi processado
$customer->fresh();
$customer->bling_id; // Deve ter o ID do Bling
$customer->bling_synced_at; // Data da sincronização
```

### 3. Verificar logs

```bash
# Logs da aplicação
tail -f storage/logs/laravel.log | grep "customer sync"

# Buscar por cliente específico
grep "customer_id.*123" storage/logs/laravel.log
```

### 4. Verificar no Bling

Acesse: **Bling → Cadastros → Contatos** e busque pelo email do cliente.

Verifique:
- Nome correto
- Email correto
- Tags: "Ecommerce" e "Site"
- Observações: "Cliente Ecommerce - Cadastrado via site"

## Logs e Monitoramento

O sistema registra logs detalhados em todas as etapas:

**Início do job**:
```
Starting customer sync to Bling
customer_id: 123, email: cliente@email.com, attempt: 1
```

**Cliente já existente no Bling**:
```
Customer already exists in Bling, linked
customer_id: 123, bling_id: 456789
```

**Sucesso**:
```
Customer synced to Bling successfully
customer_id: 123, bling_id: 456789
```

**Falha temporária**:
```
Failed to sync customer to Bling
customer_id: 123, attempt: 2
```

**Falha definitiva**:
```
Customer sync to Bling failed after all attempts
customer_id: 123
```

## Tratamento de Erros

### Cliente já existe no Bling

O job busca por email antes de criar. Se encontrar, apenas vincula o `bling_id` ao registro local.

### Token inválido/expirado

Se o access token estiver inválido:
1. Log de erro é registrado
2. Job NÃO é reenfileirado (evita loop infinito)
3. Administrador deve renovar token manualmente

### Falha na API do Bling

Tentativas automáticas (3x) com 60s de intervalo. Se todas falharem, erro é registrado nos logs.

### Duplicatas

O Bling não permite emails duplicados. Se já existir, a API retornará erro e o job vinculará ao existente.

## Sincronização Manual (se necessário)

Criar comando Artisan para sincronizar todos os clientes:

```bash
php artisan bling:sync-customers
```

**Implementação futura**:

```php
// app/Console/Commands/BlingSyncCustomers.php
public function handle()
{
    $customers = Customer::whereNotNull('email_verified_at')
        ->whereNull('bling_id')
        ->get();
    
    foreach ($customers as $customer) {
        SyncCustomerToBling::dispatch($customer);
    }
    
    $this->info("Enfileirados {$customers->count()} clientes para sincronização");
}
```

## Próximos Passos

1. **Webhook reverso**: Receber atualizações do Bling quando cliente for editado lá
2. **Sincronização de pedidos**: Enviar pedidos para o Bling após pagamento
3. **Dashboard**: Painel mostrando status de sincronização (total sincronizados, pendentes, falhas)
4. **Retry manual**: Interface para administrador reprocessar clientes que falharam

## Troubleshooting

### Job não está sendo processado

**Problema**: Cliente verificou email mas não aparece no Bling.

**Soluções**:
1. Verificar se queue worker está rodando: `php artisan queue:work`
2. Verificar failed_jobs: `php artisan queue:failed`
3. Verificar logs: `tail -f storage/logs/laravel.log`

### Token do Bling expirado

**Problema**: Logs mostram "Bling access token not found".

**Solução**:
1. Acessar: http://localhost:8000/bling
2. Clicar em "Autorizar Bling"
3. Fazer login e autorizar
4. Novo token será salvo no Redis

### Cliente não aparece no Bling

**Problema**: Job processou mas cliente não está lá.

**Verificar**:
1. Logs da aplicação para erros da API
2. Se `bling_id` foi salvo no banco
3. Se há duplicatas (buscar por email no Bling)

## API Reference

### BlingCustomerService::createCustomer()

```php
/**
 * @param Customer $customer
 * @return array|null Dados do cliente criado ou null em caso de erro
 */
public function createCustomer(Customer $customer): ?array
```

### SyncCustomerToBling::dispatch()

```php
/**
 * @param Customer $customer
 * @return void
 */
SyncCustomerToBling::dispatch($customer);
```

---

**Última atualização**: 25/11/2025
**Versão**: 2.0

---

## Atualização de Perfil e Campos PF/PJ

### Campos Adicionais (v2.0)

A partir da versão 2.0, o sistema suporta campos diferenciados para Pessoa Física (PF) e Pessoa Jurídica (PJ):

**Novos campos na tabela `customers`**:
```sql
person_type CHAR(1) DEFAULT 'F'          -- 'F' = Física, 'J' = Jurídica
birth_date DATE NULLABLE                  -- Data de nascimento (só PF)
fantasy_name VARCHAR(255) NULLABLE        -- Nome fantasia (só PJ)
state_registration VARCHAR(20) NULLABLE   -- Inscrição Estadual (IE)
nfe_email VARCHAR(255) NULLABLE           -- Email para NF-e (opcional)
phone_commercial VARCHAR(20) NULLABLE     -- Telefone comercial
taxpayer_type TINYINT DEFAULT 9           -- 1=Contribuinte ICMS, 2=Isento, 9=Não contribuinte
```

### Arquitetura de Atualização

**Fluxo**: WordPress → Laravel → Bling

```
Cliente edita perfil no WordPress
  ↓
WordPress salva localmente (user_meta)
  ↓
Hook profile_update dispara automaticamente
  ↓
WordPress → PUT /api/customers/me (Laravel)
  ↓
Laravel valida e atualiza banco
  ↓
SyncCustomerToBling job disparado (se alterou campos do Bling)
  ↓
Bling atualizado em background
```

### Endpoints API

#### GET /api/customers/me (autenticado)

Busca dados completos do cliente autenticado.

**Headers**:
```
Authorization: Bearer {token}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "João Silva",
    "email": "joao@email.com",
    "cpf_cnpj": "12345678901",
    "phone": "11999999999",
    "person_type": "F",
    "birth_date": "1990-01-15",
    "fantasy_name": null,
    "state_registration": null,
    "nfe_email": "nfe@email.com",
    "phone_commercial": null,
    "taxpayer_type": 9,
    "email_verified": true,
    "bling_id": "17796661401",
    "bling_synced_at": "2025-11-25 14:54:33"
  }
}
```

#### PUT /api/customers/me (autenticado)

Atualiza perfil do cliente autenticado.

**Headers**:
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body (todos os campos opcionais)**:
```json
{
  "name": "João Silva Santos",
  "phone": "11988888888",
  "person_type": "J",
  "cpf_cnpj": "12345678000199",
  "fantasy_name": "Silva Comércio",
  "state_registration": "123456789",
  "birth_date": null,
  "nfe_email": "fiscal@empresa.com",
  "phone_commercial": "1133334444",
  "taxpayer_type": 1
}
```

**Response**:
```json
{
  "success": true,
  "message": "Perfil atualizado com sucesso!",
  "data": {
    "id": 1,
    "name": "João Silva Santos",
    "email": "joao@email.com",
    "cpf_cnpj": "12345678000199",
    "person_type": "J",
    ...
  }
}
```

### Validações (FormRequest)

**Arquivo**: `app/Http/Requests/UpdateCustomerProfileRequest.php`

**Regras principais**:

- `person_type`: Obrigatório se enviado, valores: 'F' ou 'J'
- `cpf_cnpj`: CPF (11 dígitos) se PF, CNPJ (14 dígitos) se PJ
- `birth_date`: Opcional, data válida antes de hoje
- `state_registration`: Obrigatória se `taxpayer_type = 1`
- `taxpayer_type`: 1 (Contribuinte ICMS), 2 (Isento), 9 (Não contribuinte)

**Validações adicionais**:

- Se `person_type = F` e enviar CPF → valida 11 dígitos
- Se `person_type = J` e enviar CNPJ → valida 14 dígitos
- Se `taxpayer_type = 1` (contribuinte) → exige CNPJ e IE
- Email e cpf_cnpj devem ser únicos (exceto do próprio usuário)

### WordPress: Hook Automático

**Arquivo**: `includes/class-customer-sync.php`

**Hooks registrados**:

```php
add_action('profile_update', 'sync_customer_on_update'); // Quando cliente atualiza perfil
add_action('user_register', 'sync_customer_on_create');   // Quando cliente é criado
```

**Comportamento**:

1. Cliente salva perfil no WordPress
2. Hook `profile_update` é disparado automaticamente
3. WordPress coleta dados dos `user_meta`:
   - `billing_phone`, `phone`
   - `billing_cpf`, `cpf`
   - `billing_cnpj`, `cnpj`
   - `person_type`, `birth_date`, `fantasy_name`, etc
4. Verifica se usuário tem token (`rodust_api_token`)
5. Se tiver token → `PUT /api/customers/me` (autenticado)
6. Se não tiver → `POST /api/customers/sync-from-wordpress` (sync manual)

### Payload Bling Atualizado

**Campos enviados para Bling** (via `BlingCustomerService`):

```php
[
    'nome' => 'Nome completo ou Nome fantasia',
    'codigo' => '1', // ID do cliente no Laravel
    'situacao' => 'A',
    'tipo' => 'F', // Pessoa Física ou J = Jurídica
    'numeroDocumento' => '12345678901', // CPF ou CNPJ
    'indicadorIe' => 9, // 1 = Contribuinte, 9 = Não contribuinte
    'contribuinte' => 9, // 1 = ICMS, 2 = Isento, 9 = Não contribuinte
    'ie' => '123456789', // Inscrição Estadual (se PJ e contribuinte)
    'email' => 'nfe@email.com', // Usa nfe_email se preenchido, senão email
    'celular' => '11999999999',
    'foneComercial' => '1133334444', // Telefone comercial (se preenchido)
    'dataNascimento' => '1990-01-15', // Data de nascimento (se PF)
    'nomeFfantasia' => 'Empresa Ltda', // Nome fantasia (se PJ)
    'tiposContato' => [
        ['id' => 14582508901] // Tipo "Cliente ecommerce"
    ],
    'dadosAdicionais' => [
        'observacoes' => 'Cliente Ecommerce - Cadastrado via site'
    ]
]
```

### Checkout: Checkbox "Compra para Revenda"

**Lógica**:

1. Checkbox só é habilitado se:
   - `cpf_cnpj` não é vazio (deve ter CNPJ)
   - `state_registration` não é vazio (deve ter IE cadastrada)

2. Se desabilitado, exibir mensagem:
   ```
   "Para comprar para revenda, cadastre CNPJ e Inscrição Estadual no seu perfil"
   ```

3. Quando marcado:
   - Chama `PUT /api/customers/me` com `taxpayer_type: 1`
   - Cliente passa a ser "Contribuinte ICMS"
   - Bling recebe `indicadorIe: 1`, `contribuinte: 1`

4. Quando desmarcado:
   - Chama `PUT /api/customers/me` com `taxpayer_type: 9`
   - Cliente volta a ser "Não contribuinte"

**AJAX WordPress**:

```javascript
jQuery.ajax({
    url: rodustEcommerce.ajaxUrl,
    method: 'POST',
    data: {
        action: 'rodust_update_taxpayer_type',
        nonce: rodustEcommerce.checkoutNonce,
        taxpayer_type: isChecked ? 1 : 9
    },
    success: function(response) {
        if (response.success) {
            console.log('Taxpayer type updated:', response.data.taxpayer_type);
        }
    }
});
```

### Sincronização Manual (WordPress Admin)

**Página**: Admin → Sincronização → Clientes

**Endpoint**: `POST /api/customers/sync-from-wordpress`

**Comportamento**:

1. Busca até 100 usuários do WordPress
2. Coleta dados de cada usuário (user_meta)
3. Envia array de clientes para Laravel
4. Laravel cria novos ou atualiza existentes
5. Dispara jobs `SyncCustomerToBling` para clientes sem `bling_id`

**Response**:
```json
{
  "success": true,
  "message": "Sincronização concluída",
  "stats": {
    "created": 5,
    "updated": 10,
    "synced_to_bling": 5,
    "errors": 0
  }
}
```

### Testando Atualização de Perfil

```bash
# 1. Fazer login no WordPress como cliente
# 2. Editar perfil e alterar nome
# 3. Verificar logs do Laravel

docker exec ecommerce-laravel.test-1 tail -f storage/logs/laravel.log

# Deve aparecer:
# "=== RODUST CUSTOMER UPDATE HOOK ==="
# "User ID: 1"
# "Customer data prepared: ..."
# "Laravel response: ..."
```

### Comandos Úteis

```bash
# Ver clientes com bling_id
php artisan tinker
>>> Customer::whereNotNull('bling_id')->count();

# Ver clientes por taxpayer_type
>>> Customer::where('taxpayer_type', 1)->get(); // Contribuintes
>>> Customer::where('taxpayer_type', 9)->get(); // Não contribuintes

# Forçar sincronização de um cliente
>>> $c = Customer::find(1);
>>> SyncCustomerToBling::dispatch($c);
```

### Troubleshooting

**Perfil atualizado no WordPress mas não no Laravel**:
1. Verificar se hook está registrado: `includes/class-customer-sync.php` incluído
2. Verificar logs do WordPress: `/wp-content/debug.log`
3. Verificar se API Laravel está respondendo: `GET /api/customers/me`

**Checkbox revenda sempre desabilitada**:
1. Verificar se cliente tem CNPJ: `GET /api/customers/me` → `cpf_cnpj` deve ter 14 dígitos
2. Verificar se tem IE: `state_registration` não pode ser vazio
3. Se não tiver, redirecionar para página de perfil

**taxpayer_type não atualiza no Bling**:
1. Verificar logs: `storage/logs/laravel.log` buscar "SyncCustomerToBling"
2. Verificar se job foi processado: `php artisan queue:work`
3. Verificar payload enviado: campo `contribuinte` e `indicadorIe`

---

**Última atualização**: 25/11/2025
**Versão**: 2.0
