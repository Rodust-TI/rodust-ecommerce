# 🔒 Módulo Customer - Isolado e Protegido

## ⚠️ ATENÇÃO

Este módulo controla **TODA** autenticação e gestão de clientes do ecommerce.
**Alterações neste código podem afetar login/cadastro de TODOS os clientes.**

## 📁 Estrutura

```
app/Services/Customer/
├── CustomerAuthService.php           # Login, Logout, Verificação
├── CustomerProfileService.php        # Atualização de perfil (GET/PUT /me)
├── CustomerRegistrationService.php   # Cadastro e email de verificação
└── CustomerRecoveryService.php       # Recuperação de senha

tests/Feature/Customer/
└── CustomerAuthServiceTest.php       # Testes automatizados
```

## 🎯 Responsabilidades de Cada Service

### 1. CustomerAuthService
**O QUE FAZ:**
- Login de clientes
- Logout (revogação de token)
- Verificação de credenciais
- Validação de permissão de login

**NÃO MEXER SE:**
- Login está funcionando normalmente
- Logout está funcionando normalmente

**EXEMPLO DE USO:**
```php
$authService = new CustomerAuthService();

// Login
$result = $authService->login('email@exemplo.com', 'senha123');
// Retorna: ['customer' => Customer, 'token' => 'token_string']

// Logout
$authService->logout($customer);
```

---

### 2. CustomerProfileService
**O QUE FAZ:**
- Atualização de dados pessoais
- Sincronização automática com Bling
- Formatação de dados de perfil

**NÃO MEXER SE:**
- Atualização de perfil está funcionando
- Sincronização com Bling está ok

**EXEMPLO DE USO:**
```php
$profileService = new CustomerProfileService();

// Atualizar perfil
$customer = $profileService->updateProfile($customer, [
    'name' => 'Novo Nome',
    'phone' => '11999887766',
    'person_type' => 'F'
]);

// Obter dados formatados
$data = $profileService->getProfileData($customer);
```

---

### 3. CustomerRegistrationService
**O QUE FAZ:**
- Cadastro de novos clientes
- Envio de email de verificação
- Reenvio de email de verificação
- Verificação de email via token

**NÃO MEXER SE:**
- Cadastro está funcionando
- Emails de verificação estão chegando

**EXEMPLO DE USO:**
```php
$registrationService = new CustomerRegistrationService();

// Registrar novo cliente
$customer = $registrationService->register([
    'name' => 'João Silva',
    'email' => 'joao@exemplo.com',
    'cpf' => '12345678901',
    'password' => 'senha123'
]);

// Reenviar email de verificação
$registrationService->resendVerification('joao@exemplo.com');

// Verificar email
$customer = $registrationService->verifyEmail('token_aqui');
```

---

### 4. CustomerRecoveryService
**O QUE FAZ:**
- Recuperação de senha (esqueci minha senha)
- Reset de senha via token
- Validação de token de reset

**NÃO MEXER SE:**
- Recuperação de senha está funcionando

**EXEMPLO DE USO:**
```php
$recoveryService = new CustomerRecoveryService();

// Iniciar recuperação de senha
$recoveryService->initPasswordReset('email@exemplo.com');

// Redefinir senha
$customer = $recoveryService->resetPassword('token', 'nova_senha123');

// Verificar se token é válido
$isValid = $recoveryService->isValidResetToken('token');
```

---

## 🧪 Testes Automatizados

### O que são Testes Unitários?

Imagine que você precisa testar se o login funciona. Manualmente, você:
1. Abre o navegador (30 segundos)
2. Vai até a página de login (10 segundos)
3. Preenche email e senha (10 segundos)
4. Clica em entrar (5 segundos)
5. Verifica se logou (5 segundos)

**TOTAL: ~1 minuto por teste**

Com testes automatizados, o computador faz isso em **0.2 segundos**.

### Como Rodar os Testes

```bash
# Rodar TODOS os testes do módulo Customer
php artisan test tests/Feature/Customer

# Rodar apenas testes de autenticação
php artisan test --filter=CustomerAuthServiceTest

# Rodar um teste específico
php artisan test --filter=test_can_login_with_valid_credentials
```

### Exemplo de Saída

```
PASS  Tests\Feature\Customer\CustomerAuthServiceTest
✓ can login with valid credentials                    0.12s
✓ cannot login with wrong password                    0.08s
✓ cannot login with unverified email                  0.09s
✓ cannot login with nonexistent email                 0.07s
✓ can verify valid credentials                        0.08s

Tests:    5 passed (5 assertions)
Duration: 0.44s
```

---

## 🔐 Quando Mexer Neste Código

### ✅ PODE MEXER SE:

1. **Adicionar novos campos ao perfil**
   - Editar `CustomerProfileService::updateProfile()`
   - Adicionar campo ao array de `$blingFields` se precisar sincronizar

2. **Mudar regras de validação de senha**
   - Editar validação no controller
   - Não mexer na lógica de hash

3. **Adicionar logs ou métricas**
   - Adicionar `Log::info()` nos services
   - Não alterar a lógica principal

### ❌ NÃO MEXER SE:

1. **Login/Logout está funcionando**
2. **Cadastro está funcionando**
3. **Recuperação de senha está ok**
4. **Emails estão chegando**

### ⚠️ CUIDADO EXTRA:

- **Hash de senha**: Já é feito automaticamente pelo Model
- **Tokens**: Criados pelo Laravel Sanctum automaticamente
- **Email verificado**: Campo `email_verified_at` controla acesso

---

## 📊 Fluxo Completo de Cadastro

```
1. Cliente preenche formulário
   ↓
2. CustomerRegistrationService::register()
   - Valida CPF
   - Cria customer no banco (email_verified_at = null)
   - Gera token de verificação
   ↓
3. CustomerRegistrationService::sendVerificationEmail()
   - Envia email com link
   ↓
4. Cliente clica no link
   ↓
5. CustomerRegistrationService::verifyEmail()
   - Marca email como verificado (email_verified_at = now())
   - Dispara sync com Bling
   ↓
6. Cliente pode fazer login via CustomerAuthService::login()
```

---

## 📊 Fluxo Completo de Atualização de Perfil

```
1. Cliente altera telefone no formulário
   ↓
2. Frontend envia PUT /api/customers/me com phone sem máscara
   ↓
3. CustomerProfileService::updateProfile()
   - Atualiza campo phone no banco
   - Detecta que phone está em $blingFields
   - Recarrega customer com addresses
   ↓
4. Dispara SyncCustomerToBling
   ↓
5. Job recarrega customer com addresses
   ↓
6. BlingCustomerService::updateCustomer()
   - Envia PUT para Bling com TODOS os dados (incluindo addresses)
   ↓
7. Bling atualiza cadastro completo
```

---

## 🚀 Próximos Passos (Futuro)

- [ ] Adicionar mais testes (ProfileService, RegistrationService)
- [ ] Criar controllers isolados (AuthController, ProfileController, etc)
- [ ] Separar rotas em routes/api-customer.php
- [ ] Adicionar métricas (tempo de login, taxa de erro)
- [ ] Implementar rate limiting (limitar tentativas de login)

---

## 📞 Em Caso de Dúvida

**Antes de mexer neste código:**
1. Rode os testes: `php artisan test tests/Feature/Customer`
2. Se todos passarem ✅, está ok para mexer
3. Se algum falhar ❌, NÃO MEXER até corrigir

**Após mexer no código:**
1. Rode os testes novamente
2. Se algum falhar, desfaça as alterações
3. Se todos passarem, está ok para commit

---

## 🎓 Aprendendo Mais Sobre Testes

- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)

**Teste é como um seguro do seu código: você torce para nunca precisar, mas fica muito mais tranquilo sabendo que tem!** 🛡️
