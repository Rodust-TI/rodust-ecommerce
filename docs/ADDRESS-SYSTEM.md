# 📍 Sistema de Endereços - Nova Lógica

## 🎯 Conceito Simplificado

Cada cliente pode ter **até 5 endereços** cadastrados. De todos esses endereços, pode marcar:
- ✅ **1 como Entrega** (shipping)
- ✅ **1 como Cobrança** (billing)
- ✅ **O mesmo endereço pode ser Entrega E Cobrança**
- ⚪ **Endereços sem marcação ficam disponíveis para uso futuro**

---

## 🔄 Como Funciona

### Cadastro de Endereço
1. Usuário preenche apenas os dados do endereço (CEP, rua, número, etc)
2. **NÃO** escolhe tipo no cadastro
3. Endereço é criado sem tipo (NULL)

### Definição de Tipo
Pode ser feita de **2 formas**:

#### 1️⃣ Na Lista de Endereços (Badges Clicáveis)
```
┌─────────────────────────────────────────────────────┐
│ Rua João Hermínio, 90 - Taquaral                   │
│ [🟢 Entrega] [🟢 Cobrança]    [Editar] [Excluir]   │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│ Av. Dois Córregos, 2650 - Piracicaba               │
│ [⚪ Entrega] [⚪ Cobrança]    [Editar] [Excluir]   │
└─────────────────────────────────────────────────────┘
```

- **Badge verde** = Ativo (é endereço de entrega/cobrança)
- **Badge cinza** = Inativo (clique para ativar)
- **Clicar na badge** = Toggle o tipo daquele endereço

#### 2️⃣ No Formulário de Edição
- Checkboxes ou botões para marcar como:
  - [ ] Endereço de Entrega
  - [ ] Endereço de Cobrança
  - Pode marcar ambos, apenas um, ou nenhum

---

## 🔒 Regras de Negócio

### Limite de Endereços
- ❌ Máximo de 5 endereços por cliente
- ❌ Não pode criar 6º endereço (retorna erro)
- ✅ Pode excluir e criar novo

### Tipos Únicos
- ❌ Não pode ter 2 endereços marcados como "Entrega"
- ❌ Não pode ter 2 endereços marcados como "Cobrança"
- ✅ Ao marcar um novo, desmarca o anterior automaticamente

### Sincronização Bling
- ✅ **Shipping** → `endereco.geral` no Bling
- ✅ **Billing** → `endereco.cobranca` no Bling
- ⚪ **NULL** (sem tipo) → **NÃO** sincroniza com Bling

---

## 🛠️ API Endpoints

### GET /api/customers/addresses
Lista todos os endereços do cliente autenticado.

**Response:**
```json
{
  "success": true,
  "data": {
    "addresses": [
      {
        "id": 1,
        "type": "shipping",
        "address": "Rua João Hermínio Tricanico",
        "number": "90",
        ...
      },
      {
        "id": 2,
        "type": "billing",
        ...
      },
      {
        "id": 3,
        "type": null,
        ...
      }
    ]
  }
}
```

### POST /api/customers/addresses
Cria novo endereço (sem tipo definido inicialmente).

**Payload:**
```json
{
  "zipcode": "13421717",
  "address": "Rua João Hermínio Tricanico",
  "number": "90",
  "complement": "",
  "neighborhood": "Taquaral",
  "city": "Piracicaba",
  "state": "SP",
  "country": "BR",
  "label": "Casa" // opcional
}
```

**NÃO envia:**
- ❌ `type` (será NULL)
- ❌ `is_default` (não existe mais)

### PUT /api/customers/addresses/{id}
Atualiza dados do endereço (pode incluir ou remover tipo).

**Payload:**
```json
{
  "type": "shipping", // ou "billing" ou null
  "address": "Rua Nova",
  ...
}
```

### POST /api/customers/addresses/{id}/toggle-type
Toggle o tipo do endereço (shipping/billing/none).

**Payload:**
```json
{
  "type": "shipping" // ou "billing" ou "none" (remove tipo)
}
```

**Response:**
```json
{
  "success": true,
  "message": "Endereço definido como entrega!",
  "data": {
    "address": { ... }
  }
}
```

### DELETE /api/customers/addresses/{id}
Exclui endereço.

---

## 💾 Banco de Dados

### Estrutura da Tabela
```sql
customer_addresses
├── id (bigint)
├── customer_id (bigint)
├── type (enum: 'shipping', 'billing', NULL)  ← Único por cliente
├── label (string, nullable)
├── recipient_name (string, nullable)
├── zipcode (string, 8 chars)
├── address (string)
├── number (string)
├── complement (string, nullable)
├── neighborhood (string)
├── city (string)
├── state (string, 2 chars)
├── country (string, 2 chars, default 'BR')
├── created_at (timestamp)
├── updated_at (timestamp)
└── deleted_at (timestamp, nullable)
```

**Removido:**
- ❌ `is_default` (não existe mais)

---

## 🎨 Frontend (WordPress)

### Lista de Endereços
```php
foreach ($addresses as $address) {
    $isShipping = $address->type === 'shipping';
    $isBilling = $address->type === 'billing';
    
    // Badges clicáveis
    echo '<div class="address-badges">';
    
    // Badge Entrega
    echo '<button 
            class="badge ' . ($isShipping ? 'badge-green' : 'badge-gray') . '"
            onclick="toggleType(' . $address->id . ', \'shipping\')">';
    echo $isShipping ? '🟢 Entrega' : '⚪ Entrega';
    echo '</button>';
    
    // Badge Cobrança
    echo '<button 
            class="badge ' . ($isBilling ? 'badge-green' : 'badge-gray') . '"
            onclick="toggleType(' . $address->id . ', \'billing\')">';
    echo $isBilling ? '🟢 Cobrança' : '⚪ Cobrança';
    echo '</button>';
    
    echo '</div>';
}
```

### Formulário (Criar/Editar)
```html
<!-- Remover select de tipo -->
❌ <select name="type">...</select>

<!-- Remover checkbox padrão -->
❌ <input type="checkbox" name="is_default">

<!-- Adicionar toggles de tipo (apenas no edit) -->
<div class="type-toggles">
    <label>
        <input type="checkbox" name="is_shipping" <?= $address->type === 'shipping' ? 'checked' : '' ?>>
        Usar como endereço de entrega
    </label>
    
    <label>
        <input type="checkbox" name="is_billing" <?= $address->type === 'billing' ? 'checked' : '' ?>>
        Usar como endereço de cobrança
    </label>
</div>
```

---

## 🔧 Comandos Úteis

```bash
# Listar endereços de clientes
docker compose exec laravel.test php artisan customers:list-with-addresses

# Testar sincronização com Bling
docker compose exec laravel.test php artisan bling:test-address-sync {customer_id}

# Limpar duplicados (após migração)
docker compose exec laravel.test php artisan addresses:clean-duplicates
```

---

## 🚀 Migração do Sistema Antigo

Se você tinha o sistema antigo com `is_default`:

```bash
# 1. Rodar migration para remover coluna
docker compose exec laravel.test php artisan migrate

# 2. Limpar duplicados (mantém primeiro de cada tipo)
docker compose exec laravel.test php artisan addresses:clean-duplicates

# 3. Verificar resultado
docker compose exec laravel.test php artisan customers:list-with-addresses
```

---

## ✅ Vantagens do Novo Sistema

1. **Mais Simples:** Usuário cadastra endereço e depois define uso
2. **Mais Flexível:** Mesmo endereço pode ser entrega E cobrança
3. **Mais Intuitivo:** Badges visuais mostram claramente o status
4. **Mais Rápido:** Toggle direto na lista sem abrir modal
5. **Menos Confuso:** Remove conceito de "endereço adicional" e "padrão"

---

**Última atualização:** 26/11/2025
