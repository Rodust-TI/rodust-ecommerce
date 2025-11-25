# Melhorias Planejadas - Sistema de Clientes

## ✅ Implementado

### 1. Tipo de Contato Bling
- ✅ Campo `BLING_CUSTOMER_TYPE_ID` no `.env`
- ✅ Comando `php artisan bling:list-contact-types` para listar tipos
- ✅ Endpoint `/bling/api/contact-types` para dashboard
- ✅ `BlingCustomerService` usa `tiposContato` ao invés de `tags`

**Configuração Atual:**
```env
BLING_CUSTOMER_TYPE_ID=14582508901  # Cliente ecommerce
```

---

## 📋 Próximas Implementações

### 2. Campos Adicionais no Cadastro do Cliente

#### 2.1 Migration: Adicionar campos na tabela `customers`

```php
// database/migrations/YYYY_MM_DD_add_customer_extra_fields.php
$table->date('birth_date')->nullable()->after('phone');
$table->enum('person_type', ['F', 'J'])->default('F')->after('cpf_cnpj'); // F=Física, J=Jurídica
$table->string('fantasy_name')->nullable()->after('name'); // Para PJ
$table->string('state_registration')->nullable()->after('cpf_cnpj'); // IE para PJ
$table->string('nfe_email')->nullable()->after('email'); // Email para envio de NF-e
$table->string('phone_commercial')->nullable()->after('phone');
$table->enum('taxpayer_type', ['1', '2', '9'])->default('9')->after('state_registration');
// 1 = Contribuinte ICMS
// 2 = Isento
// 9 = Não contribuinte

// Campos opcionais (análise de dados)
$table->enum('gender', ['M', 'F', 'O'])->nullable();
$table->enum('marital_status', ['solteiro', 'casado', 'divorciado', 'viuvo'])->nullable();
$table->string('occupation')->nullable();
```

#### 2.2 Estrutura de Endereços

**Tipos de endereço necessários:**
- `billing` - Cobrança (obrigatório para compra)
- `shipping` - Entrega (pode ser diferente da cobrança)
- `default` - Residencial/Principal (geral)

**Tabela `customer_addresses` já existe. Adicionar:**
```php
$table->enum('type', ['default', 'billing', 'shipping'])->default('default');
```

#### 2.3 Validações no Frontend (WordPress)

**Páginas a modificar:**
1. `/cadastro` - Registro inicial (campos básicos)
2. `/perfil` ou `/minha-conta` - Edição completa do perfil
3. `/checkout` - Seletor PF/PJ + endereços

**Campos Obrigatórios para Compra:**
- **Pessoa Física (F):**
  - Nome completo
  - CPF
  - Data de nascimento
  - Email (principal + NF-e opcional)
  - Celular
  - Endereço de cobrança completo
  - Endereço de entrega (se diferente)

- **Pessoa Jurídica (J):**
  - Razão Social (name)
  - Nome Fantasia (fantasy_name)
  - CNPJ
  - Inscrição Estadual (se contribuinte)
  - Tipo de contribuinte (1, 2 ou 9)
  - Email (principal + NF-e opcional)
  - Telefone comercial
  - Endereço de cobrança completo
  - Endereço de entrega (se diferente)

---

### 3. Fluxo de Compra PF/PJ

#### 3.1 Checkout: Seletor de Tipo de Pessoa

**Local:** Página de checkout, ANTES da finalização do pedido

**Interface proposta:**
```html
<div class="person-type-selector">
    <h3>Como deseja realizar a compra?</h3>
    <div class="radio-group">
        <label>
            <input type="radio" name="person_type" value="F" checked>
            <span>Pessoa Física (CPF)</span>
        </label>
        <label>
            <input type="radio" name="person_type" value="J">
            <span>Pessoa Jurídica (CNPJ)</span>
        </label>
    </div>
</div>

<!-- Campos dinâmicos conforme seleção -->
<div id="pf-fields" style="display:block;">
    <!-- CPF, Data Nascimento -->
</div>
<div id="pj-fields" style="display:none;">
    <!-- CNPJ, Nome Fantasia, IE, Tipo Contribuinte -->
</div>
```

**Comportamento:**
- Cliente pode alternar entre PF/PJ **sem criar outra conta**
- Ao mudar, campos são validados de acordo com o tipo
- Sistema salva `person_type` temporariamente no pedido
- Após finalizar, atualiza `customers.person_type`
- Bling recebe `tipo: 'F'` ou `'J'` conforme seleção

**IMPORTANTE:** Cliente tem **uma única conta** e pode comprar como PF ou PJ conforme necessidade do pedido.

#### 3.2 Validação no Backend (Laravel)

**API Endpoint:** `POST /api/orders` (criar pedido)

```php
// Validar campos conforme person_type
$rules = [
    'person_type' => 'required|in:F,J',
    'cpf_cnpj' => [
        'required',
        $request->person_type === 'J' ? 'cnpj' : 'cpf' // validação customizada
    ],
    'birth_date' => $request->person_type === 'F' ? 'required|date' : 'nullable',
    'fantasy_name' => $request->person_type === 'J' ? 'required' : 'nullable',
    'state_registration' => $request->person_type === 'J' && $request->taxpayer_type === '1' ? 'required' : 'nullable',
    // ...
];
```

---

### 4. Tipo de Contribuinte (ICMS)

**Valores aceitos pela API Bling:**
- `1` - Contribuinte ICMS (PJ que paga ICMS, precisa IE)
- `2` - Isento (PJ isenta de ICMS, tem IE de isento)
- `9` - Não contribuinte (PF e PJ que não são contribuintes)

**Quando exibir seletor:**
- Apenas quando `person_type === 'J'`
- Se `taxpayer_type === '1'`, campo IE é obrigatório

**Interface:**
```html
<div id="taxpayer-selector" style="display:none;"> <!-- Exibir apenas para PJ -->
    <label>Tipo de Contribuinte</label>
    <select name="taxpayer_type">
        <option value="9">Não contribuinte</option>
        <option value="1">Contribuinte ICMS (possui IE)</option>
        <option value="2">Isento (possui IE de isento)</option>
    </select>
</div>
<div id="ie-field" style="display:none;"> <!-- Exibir se taxpayer_type === 1 ou 2 -->
    <label>Inscrição Estadual</label>
    <input type="text" name="state_registration">
</div>
```

---

### 5. Atualização do BlingCustomerService

**Modificar `prepareCustomerPayload()` para incluir novos campos:**

```php
// Tipo de pessoa (F ou J) vem do banco agora
$tipoPessoa = $customer->person_type ?? 'F';

$payload = [
    'nome' => $customer->name,
    'codigo' => (string) $customer->id,
    'situacao' => 'A',
    'numeroDocumento' => $customer->cpf_cnpj ? preg_replace('/\D/', '', $customer->cpf_cnpj) : null,
    'tipo' => $tipoPessoa,
    'indicadorIe' => $customer->taxpayer_type ?? 9, // 1, 2 ou 9
    'ie' => $customer->state_registration,
    'rg' => null, // Adicionar se coletar
    'orgaoEmissor' => null,
    'email' => $customer->email,
    'emailNfe' => $customer->nfe_email ?? $customer->email,
    'celular' => $customer->phone ? preg_replace('/\D/', '', $customer->phone) : null,
    'fone' => $customer->phone_commercial ? preg_replace('/\D/', '', $customer->phone_commercial) : null,
    'nomeFfantasia' => $tipoPessoa === 'J' ? ($customer->fantasy_name ?? $customer->name) : null,
    'contribuinte' => $customer->taxpayer_type ?? 9,
    'dataNascimento' => $customer->birth_date ? $customer->birth_date->format('Y-m-d') : null,
    'sexo' => $customer->gender, // M, F ou null
    'estadoCivil' => $customer->marital_status, // solteiro, casado, etc
    'profissao' => $customer->occupation,
    // ...
];
```

---

### 6. Sincronização WordPress

**Arquivo:** `wordpress/wp-content/themes/tema/admin-sync-page.php` (ou similar)

**Adicionar botão:**
```html
<button id="sync-customers-btn" class="btn btn-primary">
    Sincronizar Clientes com Bling
</button>
```

**JavaScript:**
```javascript
document.getElementById('sync-customers-btn').addEventListener('click', async function() {
    const btn = this;
    btn.disabled = true;
    btn.textContent = 'Sincronizando...';
    
    try {
        const response = await fetch('http://localhost:8000/bling/api/sync-customers', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                limit: 100,
                only_verified: true
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(`Sucesso! ${data.total_customers} clientes enfileirados para sincronização.`);
            console.log(data.output);
        } else {
            alert('Erro: ' + data.message);
        }
    } catch (error) {
        alert('Erro ao sincronizar: ' + error.message);
    } finally {
        btn.disabled = false;
        btn.textContent = 'Sincronizar Clientes com Bling';
    }
});
```

---

### 7. Dashboard Bling: Listar Tipos de Contato

**Adicionar no dashboard (`resources/views/bling/dashboard.blade.php`):**

```html
<!-- Configuração -->
<div class="config-section">
    <h3>⚙️ Configurações</h3>
    <button onclick="listContactTypes()" class="btn btn-info">
        Listar Tipos de Contato
    </button>
    <div id="contact-types-result" style="display:none; margin-top: 10px;">
        <!-- Resultado aqui -->
    </div>
</div>

<script>
async function listContactTypes() {
    try {
        const response = await fetch('/bling/api/contact-types');
        const data = await response.json();
        
        if (data.success) {
            let html = '<h4>Tipos de Contato Disponíveis:</h4><table>';
            html += '<tr><th>ID</th><th>Descrição</th></tr>';
            
            data.tipos.forEach(tipo => {
                html += `<tr><td>${tipo.id}</td><td>${tipo.descricao}</td></tr>`;
            });
            
            html += '</table>';
            html += `<p><strong>ID Configurado:</strong> ${data.configured_id || 'Não configurado'}</p>`;
            
            if (data.cliente_ecommerce) {
                html += `<p style="color: green;">✓ Tipo "Cliente ecommerce" encontrado: ${data.cliente_ecommerce.id}</p>`;
            } else {
                html += '<p style="color: red;">✗ Tipo "Cliente ecommerce" não encontrado. Crie no painel do Bling.</p>';
            }
            
            document.getElementById('contact-types-result').innerHTML = html;
            document.getElementById('contact-types-result').style.display = 'block';
        }
    } catch (error) {
        alert('Erro: ' + error.message);
    }
}
</script>
```

---

## 🎯 Ordem de Implementação Sugerida

1. **Migration + Model** - Adicionar campos na tabela `customers`
2. **Backend Laravel** - Atualizar validações e `BlingCustomerService`
3. **Frontend WordPress (Perfil)** - Página de edição completa do perfil
4. **Frontend WordPress (Checkout)** - Seletor PF/PJ com campos dinâmicos
5. **Dashboard Bling** - Botão listar tipos de contato
6. **WordPress Admin** - Botão de sincronização de clientes
7. **Testes** - Fluxo completo: cadastro → perfil → checkout PF → checkout PJ → Bling

---

## 🔍 Considerações Importantes

### Campos Opcionais (Análise de Dados)
- **Sexo, Estado Civil, Profissão:** Úteis para segmentação de marketing
- **Recomendação:** Tornar opcionais e solicitar em momento separado (ex: formulário pós-compra com desconto)
- **Privacidade:** LGPD exige consentimento explícito para coleta de dados sensíveis

### Múltiplos Endereços
- Cliente pode ter vários endereços salvos
- No checkout, deve poder selecionar entre endereços existentes ou cadastrar novo
- Tipos: `default`, `billing`, `shipping`

### Alteração PF ↔ PJ
- **Permitir na mesma conta:** Sim, cliente único pode comprar como PF ou PJ
- **Armazenar histórico:** Criar tabela `orders` com campo `person_type_used`
- **Sincronização Bling:** Bling permite atualizar o tipo do contato via PUT `/contatos/{id}`

### Email NF-e Separado
- Útil para empresas que querem NF-e em email contábil diferente do email de login
- Campo opcional, se vazio usa email principal

---

## 📦 Arquivos a Modificar

### Laravel (Backend)
- `database/migrations/` - Nova migration
- `app/Models/Customer.php` - Adicionar campos no $fillable e $casts
- `app/Services/BlingCustomerService.php` - Atualizar payload
- `app/Http/Controllers/Api/CustomerController.php` - Validações
- `app/Http/Requests/` - Criar FormRequest para validação PF/PJ
- `resources/views/bling/dashboard.blade.php` - Botão listar tipos

### WordPress (Frontend)
- `wp-content/themes/[tema]/page-perfil.php` - Formulário completo
- `wp-content/themes/[tema]/checkout.php` - Seletor PF/PJ
- `wp-content/themes/[tema]/admin-sync.php` - Botão sync clientes
- `wp-content/themes/[tema]/functions.php` - Helpers validação CPF/CNPJ

---

## ✅ Próxima Ação Imediata

**Escolha qual implementar primeiro:**
1. Migration + campos no Model (`person_type`, `birth_date`, etc)
2. Dashboard Bling: botão listar tipos de contato
3. Checkout: seletor PF/PJ
