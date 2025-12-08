# Guia de Testes - Mensagens de Erro MercadoPago

## 🧪 Como Testar Todos os Cenários

Este guia mostra como testar cada tipo de erro usando os cartões de teste do MercadoPago.

## 📋 Preparação

**Dados Fixos para Todos os Testes:**
- **Número do Cartão (Mastercard):** `5031 4332 1540 6351`
- **Número do Cartão (Visa):** `4235 6477 2802 5682`
- **CVV:** `123`
- **Data de Vencimento:** `11/30`
- **CPF:** `12345678909`

**O que muda:** Apenas o **NOME no cartão**

---

## ✅ CENÁRIO 1: Pagamento Aprovado

### Dados
- **Nome no cartão:** `APRO`

### Resultado Esperado
```json
{
  "success": true,
  "title": "Pagamento aprovado!",
  "message": "Seu pagamento foi aprovado com sucesso. Em breve você receberá a confirmação por e-mail.",
  "message_type": "success",
  "can_retry": false,
  "should_change_payment": false
}
```

### Status no Bling
- ✅ Pedido criado com status **"Em andamento" (ID 1)**
- ✅ Campo `paid_at` preenchido
- ✅ Status local: `processing`

---

## ❌ CENÁRIO 2: Código de Segurança Inválido

### Dados
- **Nome no cartão:** `SECU`

### Resultado Esperado
```json
{
  "success": false,
  "title": "Código de segurança inválido",
  "message": "O código de segurança (CVV) está incorreto. Verifique o verso do cartão.",
  "message_type": "error",
  "can_retry": true,
  "should_change_payment": false
}
```

### Ação Sugerida
- 🔄 Permitir que o usuário tente novamente
- 🎯 Destacar campo CVV

---

## ❌ CENÁRIO 3: Data de Vencimento Inválida

### Dados
- **Nome no cartão:** `EXPI`

### Resultado Esperado
```json
{
  "success": false,
  "title": "Data de vencimento inválida",
  "message": "A data de vencimento do cartão está incorreta. Verifique e tente novamente.",
  "message_type": "error",
  "can_retry": true,
  "should_change_payment": false
}
```

### Ação Sugerida
- 🔄 Permitir nova tentativa
- 🎯 Destacar campo de data

---

## ❌ CENÁRIO 4: Saldo Insuficiente

### Dados
- **Nome no cartão:** `FUND`

### Resultado Esperado
```json
{
  "success": false,
  "title": "Saldo insuficiente",
  "message": "O cartão não possui saldo suficiente para realizar esta compra. Tente outro cartão ou forma de pagamento.",
  "message_type": "error",
  "can_retry": false,
  "should_change_payment": true
}
```

### Ação Sugerida
- 💳 Sugerir outro cartão
- 🔄 Mostrar PIX e Boleto como alternativas

---

## ❌ CENÁRIO 5: Erro no Formulário

### Dados
- **Nome no cartão:** `FORM`

### Resultado Esperado
```json
{
  "success": false,
  "title": "Dados incorretos",
  "message": "Alguns dados do cartão estão incorretos. Por favor, revise e tente novamente.",
  "message_type": "error",
  "can_retry": true,
  "should_change_payment": false
}
```

---

## ❌ CENÁRIO 6: Erro Geral

### Dados
- **Nome no cartão:** `OTHE`

### Resultado Esperado
```json
{
  "success": false,
  "title": "Pagamento recusado",
  "message": "O banco emissor recusou o pagamento. Entre em contato com seu banco ou tente outro cartão.",
  "message_type": "error",
  "can_retry": false,
  "should_change_payment": false
}
```

---

## ⏳ CENÁRIO 7: Necessita Autorização

### Dados
- **Nome no cartão:** `CALL`

### Resultado Esperado
```json
{
  "success": false,
  "title": "Autorização necessária",
  "message": "Seu banco precisa autorizar este pagamento. Entre em contato com o banco e tente novamente.",
  "message_type": "error",
  "can_retry": false,
  "should_change_payment": false
}
```

---

## ❌ CENÁRIO 8: Parcelamento Inválido

### Dados
- **Nome no cartão:** `INST`

### Resultado Esperado
```json
{
  "success": false,
  "title": "Parcelamento não disponível",
  "message": "O número de parcelas selecionado não é aceito para este cartão. Escolha outra opção.",
  "message_type": "error",
  "can_retry": true,
  "should_change_payment": false
}
```

### Ação Sugerida
- 🔢 Permitir alterar número de parcelas

---

## ❌ CENÁRIO 9: Pagamento Duplicado

### Dados
- **Nome no cartão:** `DUPL`

### Resultado Esperado
```json
{
  "success": false,
  "title": "Pagamento duplicado",
  "message": "Você já realizou um pagamento com este valor recentemente. Se precisar pagar novamente, use outro cartão.",
  "message_type": "error",
  "can_retry": false,
  "should_change_payment": true
}
```

---

## ❌ CENÁRIO 10: Cartão Desabilitado

### Dados
- **Nome no cartão:** `LOCK`

### Resultado Esperado
```json
{
  "success": false,
  "title": "Cartão desabilitado",
  "message": "Este cartão está desabilitado. Entre em contato com seu banco ou use outro cartão.",
  "message_type": "error",
  "can_retry": false,
  "should_change_payment": true
}
```

---

## ❌ CENÁRIO 11: Lista Negra

### Dados
- **Nome no cartão:** `BLAC`

### Resultado Esperado
```json
{
  "success": false,
  "title": "Pagamento não processado",
  "message": "Não foi possível processar seu pagamento. Tente com outro cartão ou forma de pagamento.",
  "message_type": "error",
  "can_retry": false,
  "should_change_payment": true
}
```

---

## 📊 Tabela Resumo dos Testes

| # | Nome | Resultado | can_retry | should_change_payment | Status Bling |
|---|------|-----------|-----------|----------------------|--------------|
| 1 | APRO | ✅ Aprovado | ❌ | ❌ | Processing (ID 1) |
| 2 | SECU | ❌ CVV inválido | ✅ | ❌ | Não criado |
| 3 | EXPI | ❌ Data inválida | ✅ | ❌ | Não criado |
| 4 | FUND | ❌ Saldo insuficiente | ❌ | ✅ | Não criado |
| 5 | FORM | ❌ Dados incorretos | ✅ | ❌ | Não criado |
| 6 | OTHE | ❌ Erro geral | ❌ | ❌ | Não criado |
| 7 | CALL | ⏳ Autorização | ❌ | ❌ | Não criado |
| 8 | INST | ❌ Parcelas inválidas | ✅ | ❌ | Não criado |
| 9 | DUPL | ❌ Duplicado | ❌ | ✅ | Não criado |
| 10 | LOCK | ❌ Cartão bloqueado | ❌ | ✅ | Não criado |
| 11 | BLAC | ❌ Lista negra | ❌ | ✅ | Não criado |

---

## 🔧 Testando via API (Postman/Insomnia)

### Endpoint
```
POST https://rodust-ecommerce-dev.loca.lt/api/payments/card
```

### Headers
```
Content-Type: application/json
Accept: application/json
```

### Body (exemplo)
```json
{
  "customer_id": 1,
  "items": [
    {
      "product_id": 1,
      "quantity": 1,
      "unit_price": 100.00
    }
  ],
  "shipping": 10.00,
  "card_token": "seu_card_token_aqui",
  "installments": 1,
  "payment_method_id": "master",
  "issuer_id": "25"
}
```

**⚠️ Importante:** Você precisa primeiro tokenizar o cartão usando o SDK do MercadoPago no frontend antes de enviar para a API.

---

## 🧪 Script de Teste Automatizado

```bash
# Criar arquivo test-payment-scenarios.sh

#!/bin/bash

# Configurações
API_URL="https://rodust-ecommerce-dev.loca.lt/api/payments/card"
CUSTOMER_ID=1

# Array de cenários de teste
declare -A scenarios=(
  ["APRO"]="Pagamento Aprovado"
  ["SECU"]="CVV Inválido"
  ["EXPI"]="Data Vencimento Inválida"
  ["FUND"]="Saldo Insuficiente"
  ["FORM"]="Dados Incorretos"
  ["OTHE"]="Erro Geral"
  ["CALL"]="Autorização Necessária"
  ["INST"]="Parcelamento Inválido"
  ["DUPL"]="Pagamento Duplicado"
  ["LOCK"]="Cartão Desabilitado"
  ["BLAC"]="Lista Negra"
)

echo "🧪 Iniciando testes de cenários de pagamento..."
echo ""

for name in "${!scenarios[@]}"; do
  echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
  echo "📋 Cenário: ${scenarios[$name]} (Nome: $name)"
  echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
  
  # Aqui você faria a chamada real à API
  # curl -X POST $API_URL -H "Content-Type: application/json" -d "{...}"
  
  echo "✅ Teste executado"
  echo ""
done

echo "🎉 Todos os testes concluídos!"
```

---

## 📝 Checklist de Testes

### Frontend
- [ ] Mensagem de sucesso exibida corretamente (APRO)
- [ ] Mensagem de erro destacando campo CVV (SECU)
- [ ] Mensagem de erro destacando data (EXPI)
- [ ] Sugestão de PIX/Boleto exibida (FUND)
- [ ] Botão "Tentar Novamente" funcional (SECU, EXPI, FORM)
- [ ] Redirecionamento após aprovação funcionando (APRO)
- [ ] Animações de erro nos campos

### Backend
- [ ] Pedido criado no Bling com status correto (APRO)
- [ ] Campo `paid_at` preenchido corretamente (APRO)
- [ ] Status local atualizado para `processing` (APRO)
- [ ] Logs registrando todos os status detalhados
- [ ] Webhook sincronizando corretamente

### Integração
- [ ] Mensagens consistentes entre frontend e backend
- [ ] Timeout de API tratado corretamente
- [ ] Retry logic funcionando para erros temporários
- [ ] Validação de campos antes de enviar

---

## 🐛 Debugging

### Ver logs em tempo real
```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Filtrar apenas pagamentos
tail -f storage/logs/laravel.log | grep -i "pagamento\|payment"
```

### Verificar pedido no banco
```sql
SELECT 
  id, 
  order_number, 
  status, 
  payment_status, 
  paid_at, 
  bling_order_number,
  created_at
FROM orders 
ORDER BY id DESC 
LIMIT 10;
```

### Verificar no Bling
1. Acessar painel do Bling
2. Menu: Vendas > Pedidos
3. Filtrar por data de hoje
4. Verificar status do pedido

---

## 📚 Referências

- [Cartões de Teste MercadoPago](https://www.mercadopago.com.br/developers/pt/docs/checkout-api/additional-content/your-integrations/test/cards)
- [Status de Pagamento](https://www.mercadopago.com.br/developers/pt/docs/checkout-api/response-handling/collection-results)
- Documentação completa: `docs/MERCADOPAGO_ERROR_MESSAGES.md`

---

**Última atualização:** 02/12/2025
