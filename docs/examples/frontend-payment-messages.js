/**
 * Exemplo de integração Frontend para tratamento de erros do MercadoPago
 * 
 * Este arquivo demonstra como consumir as mensagens amigáveis retornadas pela API
 */

// =============================================================================
// EXEMPLO 1: Classe Helper para Mensagens
// =============================================================================

class PaymentMessageHandler {
  /**
   * Exibe mensagem de retorno do pagamento
   * @param {Object} response - Resposta da API
   */
  static showMessage(response) {
    const { title, message, message_type, can_retry, should_change_payment } = response;
    
    // Exibir alert/toast baseado no tipo
    switch (message_type) {
      case 'success':
        this.showSuccess(title, message);
        break;
      case 'error':
        this.showError(title, message);
        break;
      case 'warning':
        this.showWarning(title, message);
        break;
      case 'info':
        this.showInfo(title, message);
        break;
    }
    
    // Ações específicas
    if (can_retry) {
      this.enableRetryButton();
    }
    
    if (should_change_payment) {
      this.suggestAlternativePayment();
    }
  }
  
  static showSuccess(title, message) {
    // Implementar com seu sistema de notificações
    // Ex: Toastify, SweetAlert, etc.
    alert(`✅ ${title}\n${message}`);
  }
  
  static showError(title, message) {
    alert(`❌ ${title}\n${message}`);
  }
  
  static showWarning(title, message) {
    alert(`⚠️ ${title}\n${message}`);
  }
  
  static showInfo(title, message) {
    alert(`ℹ️ ${title}\n${message}`);
  }
  
  static enableRetryButton() {
    const retryBtn = document.getElementById('retry-payment-btn');
    if (retryBtn) {
      retryBtn.style.display = 'block';
      retryBtn.disabled = false;
    }
  }
  
  static suggestAlternativePayment() {
    const alternativeSection = document.getElementById('alternative-payment-methods');
    if (alternativeSection) {
      alternativeSection.style.display = 'block';
    }
  }
}

// =============================================================================
// EXEMPLO 2: Função de Processamento de Pagamento com Cartão
// =============================================================================

async function processCardPayment(cardData) {
  const paymentButton = document.getElementById('pay-button');
  paymentButton.disabled = true;
  paymentButton.textContent = 'Processando...';
  
  try {
    const response = await fetch('/api/payments/card', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify(cardData)
    });
    
    const result = await response.json();
    
    if (result.success) {
      // ✅ PAGAMENTO APROVADO
      PaymentMessageHandler.showSuccess(result.title, result.message);
      
      // Redirecionar para página de confirmação
      setTimeout(() => {
        window.location.href = `/orders/${result.data.order.order_number}/confirmation`;
      }, 2000);
      
    } else {
      // ❌ ERRO NO PAGAMENTO
      PaymentMessageHandler.showError(result.title, result.message);
      
      // Destacar campo com erro (se especificado)
      if (result.field) {
        highlightErrorField(result.field);
      }
      
      // Sugerir ação baseada no tipo de erro
      if (result.should_change_payment) {
        showAlternativePaymentMethods();
      } else if (result.can_retry) {
        paymentButton.disabled = false;
        paymentButton.textContent = 'Tentar Novamente';
      } else {
        showContactSupport();
      }
    }
    
  } catch (error) {
    console.error('Erro ao processar pagamento:', error);
    PaymentMessageHandler.showError(
      'Erro de Conexão',
      'Não foi possível conectar ao servidor. Verifique sua internet e tente novamente.'
    );
    paymentButton.disabled = false;
    paymentButton.textContent = 'Tentar Novamente';
  }
}

// =============================================================================
// EXEMPLO 3: Funções Auxiliares de UI
// =============================================================================

function highlightErrorField(fieldName) {
  // Mapear field name para ID do campo no formulário
  const fieldMap = {
    'card_number': 'cardNumber',
    'security_code': 'securityCode',
    'expiration_month': 'expirationMonth',
    'expiration_year': 'expirationYear',
    'cardholder_name': 'cardholderName',
    'document': 'document'
  };
  
  const fieldId = fieldMap[fieldName];
  if (fieldId) {
    const field = document.getElementById(fieldId);
    if (field) {
      field.classList.add('error');
      field.focus();
      
      // Remover highlight após 3 segundos
      setTimeout(() => field.classList.remove('error'), 3000);
    }
  }
}

function showAlternativePaymentMethods() {
  const alternativeSection = document.getElementById('alternative-payments');
  if (alternativeSection) {
    alternativeSection.style.display = 'block';
    alternativeSection.scrollIntoView({ behavior: 'smooth' });
    
    // Destacar PIX e Boleto como alternativas
    document.getElementById('pix-option')?.classList.add('recommended');
    document.getElementById('boleto-option')?.classList.add('recommended');
  }
}

function showContactSupport() {
  const supportSection = document.getElementById('contact-support');
  if (supportSection) {
    supportSection.style.display = 'block';
  }
}

// =============================================================================
// EXEMPLO 4: Integração com SweetAlert2 (mais visual)
// =============================================================================

class PaymentMessageHandlerWithSwal {
  static async showMessage(response) {
    const { title, message, message_type, can_retry, should_change_payment } = response;
    
    const iconMap = {
      'success': 'success',
      'error': 'error',
      'warning': 'warning',
      'info': 'info'
    };
    
    const buttons = this.getActionButtons(can_retry, should_change_payment);
    
    const result = await Swal.fire({
      title: title,
      text: message,
      icon: iconMap[message_type] || 'info',
      confirmButtonText: buttons.confirm,
      showCancelButton: buttons.showCancel,
      cancelButtonText: buttons.cancel,
      confirmButtonColor: message_type === 'success' ? '#28a745' : '#3085d6',
      cancelButtonColor: '#d33'
    });
    
    // Ações baseadas na escolha do usuário
    if (result.isConfirmed) {
      if (can_retry) {
        // Usuário quer tentar novamente
        document.getElementById('payment-form').scrollIntoView({ behavior: 'smooth' });
      } else if (should_change_payment) {
        // Mostrar outras formas de pagamento
        showAlternativePaymentMethods();
      }
    }
  }
  
  static getActionButtons(can_retry, should_change_payment) {
    if (can_retry) {
      return {
        confirm: 'Tentar Novamente',
        showCancel: true,
        cancel: 'Cancelar'
      };
    }
    
    if (should_change_payment) {
      return {
        confirm: 'Ver Outras Formas de Pagamento',
        showCancel: true,
        cancel: 'Cancelar'
      };
    }
    
    return {
      confirm: 'OK',
      showCancel: false
    };
  }
}

// =============================================================================
// EXEMPLO 5: React Component (exemplo moderno)
// =============================================================================

/**
 * Componente React para exibir mensagens de pagamento
 */
import React, { useState } from 'react';
import { Alert, Button, Modal } from 'react-bootstrap'; // ou seu framework

const PaymentResult = ({ response, onRetry, onChangePayment }) => {
  const [show, setShow] = useState(true);
  
  if (!response) return null;
  
  const { title, message, message_type, can_retry, should_change_payment } = response;
  
  const alertVariant = {
    'success': 'success',
    'error': 'danger',
    'warning': 'warning',
    'info': 'info'
  }[message_type] || 'info';
  
  return (
    <Modal show={show} onHide={() => setShow(false)} centered>
      <Modal.Header closeButton>
        <Modal.Title>{title}</Modal.Title>
      </Modal.Header>
      <Modal.Body>
        <Alert variant={alertVariant}>
          {message}
        </Alert>
      </Modal.Body>
      <Modal.Footer>
        {can_retry && (
          <Button variant="primary" onClick={onRetry}>
            Tentar Novamente
          </Button>
        )}
        
        {should_change_payment && (
          <Button variant="warning" onClick={onChangePayment}>
            Usar Outra Forma de Pagamento
          </Button>
        )}
        
        <Button variant="secondary" onClick={() => setShow(false)}>
          Fechar
        </Button>
      </Modal.Footer>
    </Modal>
  );
};

export default PaymentResult;

// =============================================================================
// EXEMPLO 6: Vue.js Component
// =============================================================================

/**
 * Componente Vue para exibir mensagens de pagamento
 */
const PaymentResultComponent = {
  template: `
    <div v-if="show" class="modal-overlay">
      <div class="modal-content">
        <div class="modal-header" :class="'alert-' + response.message_type">
          <h3>{{ response.title }}</h3>
          <button @click="close" class="close-btn">&times;</button>
        </div>
        
        <div class="modal-body">
          <p>{{ response.message }}</p>
        </div>
        
        <div class="modal-footer">
          <button 
            v-if="response.can_retry" 
            @click="$emit('retry')" 
            class="btn btn-primary"
          >
            Tentar Novamente
          </button>
          
          <button 
            v-if="response.should_change_payment" 
            @click="$emit('change-payment')" 
            class="btn btn-warning"
          >
            Usar Outra Forma de Pagamento
          </button>
          
          <button @click="close" class="btn btn-secondary">
            Fechar
          </button>
        </div>
      </div>
    </div>
  `,
  
  props: {
    response: {
      type: Object,
      required: true
    }
  },
  
  data() {
    return {
      show: true
    };
  },
  
  methods: {
    close() {
      this.show = false;
      this.$emit('close');
    }
  }
};

// =============================================================================
// EXEMPLO 7: CSS para Estilos das Mensagens
// =============================================================================

const paymentMessageStyles = `
<style>
.payment-message {
  padding: 15px;
  margin: 20px 0;
  border-radius: 8px;
  font-family: Arial, sans-serif;
}

.payment-message.success {
  background-color: #d4edda;
  border: 1px solid #c3e6cb;
  color: #155724;
}

.payment-message.error {
  background-color: #f8d7da;
  border: 1px solid #f5c6cb;
  color: #721c24;
}

.payment-message.warning {
  background-color: #fff3cd;
  border: 1px solid #ffeaa7;
  color: #856404;
}

.payment-message.info {
  background-color: #d1ecf1;
  border: 1px solid #bee5eb;
  color: #0c5460;
}

.payment-message h4 {
  margin: 0 0 10px 0;
  font-size: 18px;
  font-weight: bold;
}

.payment-message p {
  margin: 0;
  font-size: 14px;
  line-height: 1.5;
}

.field-error {
  border: 2px solid #dc3545 !important;
  box-shadow: 0 0 5px rgba(220, 53, 69, 0.5);
  animation: shake 0.3s;
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-10px); }
  75% { transform: translateX(10px); }
}

.alternative-payment-methods {
  margin-top: 20px;
  padding: 20px;
  background-color: #f8f9fa;
  border-radius: 8px;
}

.alternative-payment-methods h4 {
  margin-bottom: 15px;
  color: #333;
}

.payment-option {
  padding: 15px;
  margin: 10px 0;
  border: 2px solid #ddd;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s;
}

.payment-option:hover {
  border-color: #007bff;
  background-color: #f0f8ff;
}

.payment-option.recommended {
  border-color: #28a745;
  background-color: #d4edda;
}

.payment-option.recommended::before {
  content: "✓ Recomendado";
  display: inline-block;
  background-color: #28a745;
  color: white;
  padding: 3px 8px;
  border-radius: 4px;
  font-size: 12px;
  margin-right: 10px;
}
</style>
`;

// =============================================================================
// EXEMPLO DE USO COMPLETO
// =============================================================================

/**
 * Exemplo de uso completo no checkout
 */
document.addEventListener('DOMContentLoaded', function() {
  const paymentForm = document.getElementById('payment-form');
  
  paymentForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Coletar dados do formulário
    const cardData = {
      customer_id: document.getElementById('customer_id').value,
      items: JSON.parse(document.getElementById('items').value),
      card_token: document.getElementById('card_token').value,
      installments: parseInt(document.getElementById('installments').value),
      payment_method_id: document.getElementById('payment_method_id').value,
      issuer_id: document.getElementById('issuer_id').value
    };
    
    // Processar pagamento
    await processCardPayment(cardData);
  });
});
