<?php

namespace App\Enums;

/**
 * Status detalhados de pagamento do MercadoPago
 * 
 * @see https://www.mercadopago.com.br/developers/pt/docs/checkout-api/response-handling/collection-results
 */
enum MercadoPagoStatusDetail: string
{
    // Pagamentos aprovados
    case ACCREDITED = 'accredited'; // Pronto! Seu pagamento foi aprovado. No resumo, você verá a cobrança do valor como statement_descriptor.
    
    // Pagamentos pendentes
    case PENDING_CONTINGENCY = 'pending_contingency'; // Estamos processando seu pagamento. Não se preocupe, em menos de 2 dias úteis informaremos por e-mail se foi creditado.
    case PENDING_REVIEW_MANUAL = 'pending_review_manual'; // Estamos processando seu pagamento. Não se preocupe, em menos de 2 dias úteis informaremos por e-mail se foi creditado ou se necessitamos de mais informação.
    case PENDING_WAITING_TRANSFER = 'pending_waiting_transfer'; // Aguardando o pagamento
    case PENDING_WAITING_PAYMENT = 'pending_waiting_payment'; // Aguardando o pagamento
    
    // Pagamentos rejeitados
    case CC_REJECTED_BAD_FILLED_CARD_NUMBER = 'cc_rejected_bad_filled_card_number'; // Revise o número do cartão.
    case CC_REJECTED_BAD_FILLED_DATE = 'cc_rejected_bad_filled_date'; // Revise a data de vencimento.
    case CC_REJECTED_BAD_FILLED_OTHER = 'cc_rejected_bad_filled_other'; // Revise os dados.
    case CC_REJECTED_BAD_FILLED_SECURITY_CODE = 'cc_rejected_bad_filled_security_code'; // Revise o código de segurança do cartão.
    case CC_REJECTED_BLACKLIST = 'cc_rejected_blacklist'; // Não pudemos processar seu pagamento.
    case CC_REJECTED_CALL_FOR_AUTHORIZE = 'cc_rejected_call_for_authorize'; // Você deve autorizar perante o emissor do cartão o pagamento.
    case CC_REJECTED_CARD_DISABLED = 'cc_rejected_card_disabled'; // Ligue para o emissor do cartão para ativá-lo. O telefone está no verso do seu cartão.
    case CC_REJECTED_CARD_ERROR = 'cc_rejected_card_error'; // Não conseguimos processar seu pagamento.
    case CC_REJECTED_DUPLICATED_PAYMENT = 'cc_rejected_duplicated_payment'; // Você já efetuou um pagamento com esse valor. Caso precise pagar novamente, utilize outro cartão ou outra forma de pagamento.
    case CC_REJECTED_HIGH_RISK = 'cc_rejected_high_risk'; // Seu pagamento foi recusado. Escolha outra forma de pagamento. Recomendamos meios de pagamento em dinheiro.
    case CC_REJECTED_INSUFFICIENT_AMOUNT = 'cc_rejected_insufficient_amount'; // O cartão possui saldo insuficiente.
    case CC_REJECTED_INVALID_INSTALLMENTS = 'cc_rejected_invalid_installments'; // O emissor do cartão não processa pagamentos em parcelas.
    case CC_REJECTED_MAX_ATTEMPTS = 'cc_rejected_max_attempts'; // Você atingiu o limite de tentativas permitido. Escolha outro cartão ou outra forma de pagamento.
    case CC_REJECTED_OTHER_REASON = 'cc_rejected_other_reason'; // O emissor do cartão não processou o pagamento.
    
    // Cancelados
    case CANCELLED = 'cancelled'; // Pagamento cancelado
    
    // Estornos
    case REFUNDED = 'refunded'; // Pagamento estornado
    case PARTIALLY_REFUNDED = 'partially_refunded'; // Pagamento parcialmente estornado
    case CHARGED_BACK = 'charged_back'; // Foi realizado um chargeback no seu pagamento
    
    // Outros
    case UNKNOWN = 'unknown'; // Status desconhecido
}
