# Sistema de Mensagens de Erro do MercadoPago

## 📋 Visão Geral

Sistema modular para mapear erros técnicos do MercadoPago em mensagens amigáveis e intuitivas para o usuário final.

## 🎯 Princípios de Design

- **SRP (Single Responsibility Principle)**: Cada classe tem uma única responsabilidade
- **Organização**: Arquivos separados por contexto
- **Manutenibilidade**: Fácil adicionar novos mapeamentos
- **Testabilidade**: Lógica isolada e testável

## 📁 Estrutura de Arquivos

```
app/
├── Enums/
│   └── MercadoPagoStatusDetail.php    # Enum com todos status detalhados
└── Services/
    └── Payment/
        └── MercadoPagoErrorMapper.php  # Mapeamento de erros para mensagens
```

## 🔧 Componentes

### 1. MercadoPagoStatusDetail (Enum)

Enumera todos os `status_detail` possíveis retornados pela API do MercadoPago.

```php
use App\Enums\MercadoPagoStatusDetail;

$statusDetail = MercadoPagoStatusDetail::ACCREDITED;
```

### 2. MercadoPagoErrorMapper (Service)

Serviço responsável por mapear erros técnicos em mensagens amigáveis.

#### Métodos Principais:

**`mapStatusDetailToMessage(string $statusDetail, ?string $status = null): array`**

Mapeia o `status_detail` do pagamento para mensagem amigável.

```php
$mapper = app(MercadoPagoErrorMapper::class);

$result = $mapper->mapStatusDetailToMessage('cc_rejected_insufficient_amount');

// Retorna:
[
    'title' => 'Saldo insuficiente',
    'message' => 'O cartão não possui saldo suficiente para realizar esta compra...',
    'type' => 'error',
    'action' => 'change_payment_method',
    'fix' => 'Use outro cartão ou forma de pagamento'
]
```

**`mapErrorCodeToMessage($errorCode, ?string $errorMessage = null): array`**

Mapeia códigos de erro da API (ex: 205, 208, 213).

```php
$result = $mapper->mapErrorCodeToMessage('213');

// Retorna:
[
    'title' => 'Digite o código de segurança',
    'message' => 'O código de segurança (CVV) é obrigatório.',
    'type' => 'error',
    'field' => 'security_code'
]
```

**`getStatusMessage(string $status): array`**

Mensagem baseada no status geral (approved, pending, rejected).

**`canRetry(string $statusDetail): bool`**

Verifica se o erro permite nova tentativa (ex: dados preenchidos incorretamente).

**`shouldChangePaymentMethod(string $statusDetail): bool`**

Verifica se deve sugerir mudança de meio de pagamento (ex: saldo insuficiente).

## 📝 Status Mapeados

### ✅ Aprovados
- `accredited` - Pagamento aprovado

### ⏳ Pendentes
- `pending_contingency` - Em análise
- `pending_review_manual` - Em revisão
- `pending_waiting_payment` - Aguardando pagamento
- `pending_waiting_transfer` - Aguardando transferência

### ❌ Erros de Preenchimento (Pode tentar novamente)
- `cc_rejected_bad_filled_card_number` - Número do cartão inválido
- `cc_rejected_bad_filled_date` - Data de vencimento inválida
- `cc_rejected_bad_filled_security_code` - CVV inválido
- `cc_rejected_bad_filled_other` - Dados incorretos

### 🚫 Problemas com Cartão (Mudar forma de pagamento)
- `cc_rejected_insufficient_amount` - Saldo insuficiente
- `cc_rejected_card_disabled` - Cartão desabilitado
- `cc_rejected_invalid_installments` - Parcelamento não disponível
- `cc_rejected_duplicated_payment` - Pagamento duplicado
- `cc_rejected_max_attempts` - Limite de tentativas excedido

### 🔒 Segurança/Fraude
- `cc_rejected_blacklist` - Lista negra
- `cc_rejected_high_risk` - Alto risco (sugerir PIX/boleto)

### 📞 Contatar Banco
- `cc_rejected_call_for_authorize` - Necessita autorização do banco
- `cc_rejected_other_reason` - Banco recusou

## 🧪 Cartões de Teste do MercadoPago

Para testar diferentes cenários, use os cartões de teste com os **nomes específicos**:

| Nome no Cartão | Resultado | Uso |
|----------------|-----------|-----|
| **APRO** | ✅ Aprovado | Testar fluxo de sucesso |
| **SECU** | ❌ CVV inválido | Testar erro de código de segurança |
| **EXPI** | ❌ Data vencimento | Testar erro de validade |
| **FORM** | ❌ Erro no formulário | Testar validação de dados |
| **FUND** | ❌ Saldo insuficiente | Testar saldo insuficiente |
| **OTHE** | ❌ Erro geral | Testar erro genérico |
| **CALL** | ⏳ Autorizar | Testar autorização bancária |
| **INST** | ❌ Parcelas inválidas | Testar parcelamento |
| **DUPL** | ❌ Duplicado | Testar pagamento duplicado |
| **LOCK** | ❌ Cartão desabilitado | Testar cartão bloqueado |
| **BLAC** | ❌ Lista negra | Testar bloqueio por segurança |

**Números de Cartão de Teste:**
- Mastercard: `5031 4332 1540 6351`
- Visa: `4235 6477 2802 5682`
- CVV: `123`
- Validade: `11/30`

🔗 [Documentação Completa MercadoPago](https://www.mercadopago.com.br/developers/pt/docs/checkout-api/additional-content/your-integrations/test/cards)

## 💻 Como Usar no Frontend

### Resposta de Sucesso
```json
{
  "success": true,
  "title": "Pagamento aprovado!",
  "message": "Seu pagamento foi aprovado com sucesso. Em breve você receberá a confirmação por e-mail.",
  "message_type": "success",
  "can_retry": false,
  "should_change_payment": false,
  "data": {
    "order": { ... },
    "payment": { ... }
  }
}
```

### Resposta de Erro
```json
{
  "success": false,
  "title": "Código de segurança inválido",
  "message": "O código de segurança (CVV) está incorreto. Verifique o verso do cartão.",
  "message_type": "error",
  "can_retry": true,
  "should_change_payment": false,
  "field": "security_code"
}
```

### Lógica no Frontend

```javascript
// Ao receber resposta do pagamento
if (response.success) {
  // Mostrar mensagem de sucesso
  showAlert(response.title, response.message, response.message_type);
  
  // Redirecionar para página de confirmação
  redirectToOrderConfirmation(response.data.order.id);
  
} else {
  // Mostrar erro
  showAlert(response.title, response.message, 'error');
  
  // Destacar campo com erro (se houver)
  if (response.field) {
    highlightField(response.field);
  }
  
  // Sugestão de ação
  if (response.should_change_payment) {
    showPaymentMethodSelector(); // Sugerir PIX, boleto, etc.
    
  } else if (response.can_retry) {
    // Permitir tentar novamente
    enableRetryButton();
  }
}
```

## 🎨 Tipos de Mensagem

- **`success`** - Verde - Pagamento aprovado
- **`warning`** - Amarelo - Pendente/Em análise
- **`error`** - Vermelho - Erro/Recusado
- **`info`** - Azul - Informação geral

## 📊 Ações Sugeridas

- **`approved`** - Redirecionar para confirmação
- **`pending`** - Aguardar notificação
- **`retry`** - Permitir nova tentativa
- **`change_payment_method`** - Sugerir outro meio de pagamento
- **`change_installments`** - Alterar número de parcelas
- **`contact_bank`** - Orientar contatar banco
- **`wait_or_change`** - Aguardar ou trocar cartão
- **`check_orders`** - Verificar pedidos anteriores

## 🔄 Fluxo de Integração

```
┌─────────────┐
│   Frontend  │
│  (Checkout) │
└──────┬──────┘
       │ POST /api/payments/card
       ▼
┌─────────────────┐
│ PaymentController│
└──────┬──────────┘
       │
       ▼
┌──────────────────┐
│ MercadoPagoService│
└──────┬───────────┘
       │ API Request
       ▼
┌─────────────────┐
│  MercadoPago API │
└──────┬──────────┘
       │ Response
       ▼
┌────────────────────┐
│MercadoPagoErrorMapper│ ← Mapeia erro/sucesso
└────────┬───────────┘
         │ Mensagem amigável
         ▼
┌─────────────────┐
│   Frontend      │ ← Exibe mensagem ao usuário
│  (Alert/Toast)  │
└─────────────────┘
```

## 🧪 Testando

1. **Testar Pagamento Aprovado:**
   - Nome: `APRO`
   - Resultado: Mensagem de sucesso

2. **Testar CVV Inválido:**
   - Nome: `SECU`
   - Resultado: "Código de segurança inválido" + `can_retry: true`

3. **Testar Saldo Insuficiente:**
   - Nome: `FUND`
   - Resultado: "Saldo insuficiente" + `should_change_payment: true`

4. **Testar Data Vencimento:**
   - Nome: `EXPI`
   - Resultado: "Data de vencimento inválida" + `can_retry: true`

## 📚 Referências

- [Cartões de Teste - MercadoPago](https://www.mercadopago.com.br/developers/pt/docs/checkout-api/additional-content/your-integrations/test/cards)
- [Status de Pagamento - MercadoPago](https://www.mercadopago.com.br/developers/pt/docs/checkout-api/response-handling/collection-results)
- [Códigos de Erro - MercadoPago](https://www.mercadopago.com.br/developers/pt/docs/checkout-api/response-handling/data-insertion-errors)

## 🆕 Adicionando Novos Mapeamentos

Para adicionar um novo `status_detail`:

1. Adicione ao enum `MercadoPagoStatusDetail.php`
2. Adicione o case no método `mapStatusDetailToMessage()` do `MercadoPagoErrorMapper.php`
3. Teste com cartão de teste correspondente

```php
// Exemplo de novo mapeamento
'cc_rejected_new_reason' => [
    'title' => 'Título do erro',
    'message' => 'Mensagem explicativa',
    'type' => 'error',
    'action' => 'retry',
    'fix' => 'Como resolver'
],
```

---

**Última atualização:** 02/12/2025
