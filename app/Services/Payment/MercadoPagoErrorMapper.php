<?php

namespace App\Services\Payment;

/**
 * Serviço responsável por mapear erros do MercadoPago para mensagens amigáveis ao usuário
 * 
 * Segue o princípio de Single Responsibility (SRP) - única responsabilidade: traduzir erros técnicos
 * 
 * @see https://www.mercadopago.com.br/developers/pt/docs/checkout-api/additional-content/your-integrations/test/cards
 * @see https://www.mercadopago.com.br/developers/pt/docs/checkout-api/response-handling/collection-results
 */
class MercadoPagoErrorMapper
{
    /**
     * Mapeia o status_detail do MercadoPago para mensagem amigável
     * 
     * @param string $statusDetail Status detalhado retornado pela API
     * @param string|null $status Status geral do pagamento
     * @return array ['title' => string, 'message' => string, 'type' => string]
     */
    public function mapStatusDetailToMessage(string $statusDetail, ?string $status = null): array
    {
        return match ($statusDetail) {
            // ✅ APROVADOS
            'accredited' => [
                'title' => 'Pagamento aprovado!',
                'message' => 'Seu pagamento foi aprovado com sucesso. Em breve você receberá a confirmação por e-mail.',
                'type' => 'success',
                'action' => 'approved'
            ],
            
            // ⏳ PENDENTES
            'pending_contingency' => [
                'title' => 'Pagamento em análise',
                'message' => 'Estamos processando seu pagamento. Em menos de 2 dias úteis informaremos por e-mail se foi aprovado.',
                'type' => 'warning',
                'action' => 'pending'
            ],
            
            'pending_review_manual' => [
                'title' => 'Pagamento em revisão',
                'message' => 'Estamos analisando seu pagamento. Em breve entraremos em contato por e-mail.',
                'type' => 'warning',
                'action' => 'pending'
            ],
            
            'pending_waiting_payment', 'pending_waiting_transfer' => [
                'title' => 'Aguardando pagamento',
                'message' => 'Estamos aguardando a confirmação do seu pagamento.',
                'type' => 'info',
                'action' => 'pending'
            ],
            
            // ❌ ERROS DE PREENCHIMENTO
            'cc_rejected_bad_filled_card_number' => [
                'title' => 'Número do cartão inválido',
                'message' => 'Por favor, verifique o número do cartão e tente novamente.',
                'type' => 'error',
                'action' => 'retry',
                'fix' => 'Revise o número do cartão'
            ],
            
            'cc_rejected_bad_filled_date' => [
                'title' => 'Data de vencimento inválida',
                'message' => 'A data de vencimento do cartão está incorreta. Verifique e tente novamente.',
                'type' => 'error',
                'action' => 'retry',
                'fix' => 'Revise a data de vencimento'
            ],
            
            'cc_rejected_bad_filled_security_code' => [
                'title' => 'Código de segurança inválido',
                'message' => 'O código de segurança (CVV) está incorreto. Verifique o verso do cartão.',
                'type' => 'error',
                'action' => 'retry',
                'fix' => 'Revise o código de segurança (CVV)'
            ],
            
            'cc_rejected_bad_filled_other' => [
                'title' => 'Dados incorretos',
                'message' => 'Alguns dados do cartão estão incorretos. Por favor, revise e tente novamente.',
                'type' => 'error',
                'action' => 'retry',
                'fix' => 'Revise todos os dados do cartão'
            ],
            
            // ❌ PROBLEMAS COM O CARTÃO
            'cc_rejected_insufficient_amount' => [
                'title' => 'Saldo insuficiente',
                'message' => 'O cartão não possui saldo suficiente para realizar esta compra. Tente outro cartão ou forma de pagamento.',
                'type' => 'error',
                'action' => 'change_payment_method',
                'fix' => 'Use outro cartão ou forma de pagamento'
            ],
            
            'cc_rejected_card_disabled' => [
                'title' => 'Cartão desabilitado',
                'message' => 'Este cartão está desabilitado. Entre em contato com seu banco ou use outro cartão.',
                'type' => 'error',
                'action' => 'change_payment_method',
                'fix' => 'Entre em contato com o banco ou use outro cartão'
            ],
            
            'cc_rejected_call_for_authorize' => [
                'title' => 'Autorização necessária',
                'message' => 'Seu banco precisa autorizar este pagamento. Entre em contato com o banco e tente novamente.',
                'type' => 'error',
                'action' => 'contact_bank',
                'fix' => 'Ligue para o banco para autorizar o pagamento'
            ],
            
            'cc_rejected_invalid_installments' => [
                'title' => 'Parcelamento não disponível',
                'message' => 'O número de parcelas selecionado não é aceito para este cartão. Escolha outra opção.',
                'type' => 'error',
                'action' => 'change_installments',
                'fix' => 'Escolha outro número de parcelas'
            ],
            
            // ❌ SEGURANÇA / FRAUDE
            'cc_rejected_blacklist' => [
                'title' => 'Pagamento não processado',
                'message' => 'Não foi possível processar seu pagamento. Tente com outro cartão ou forma de pagamento.',
                'type' => 'error',
                'action' => 'change_payment_method',
                'fix' => 'Use outro cartão ou meio de pagamento'
            ],
            
            'cc_rejected_high_risk' => [
                'title' => 'Pagamento recusado por segurança',
                'message' => 'Por questões de segurança, este pagamento foi recusado. Recomendamos usar PIX ou boleto.',
                'type' => 'error',
                'action' => 'change_payment_method',
                'fix' => 'Use PIX, boleto ou outro cartão'
            ],
            
            'cc_rejected_max_attempts' => [
                'title' => 'Limite de tentativas excedido',
                'message' => 'Você atingiu o número máximo de tentativas. Por favor, aguarde alguns minutos ou use outro cartão.',
                'type' => 'error',
                'action' => 'wait_or_change',
                'fix' => 'Aguarde alguns minutos ou use outro cartão'
            ],
            
            // ❌ DUPLICAÇÃO
            'cc_rejected_duplicated_payment' => [
                'title' => 'Pagamento duplicado',
                'message' => 'Você já realizou um pagamento com este valor recentemente. Se precisar pagar novamente, use outro cartão.',
                'type' => 'error',
                'action' => 'check_orders',
                'fix' => 'Verifique seus pedidos ou use outro cartão'
            ],
            
            // ❌ ERRO NO CARTÃO/EMISSOR
            'cc_rejected_card_error' => [
                'title' => 'Erro no cartão',
                'message' => 'Houve um problema ao processar seu cartão. Tente novamente ou use outro cartão.',
                'type' => 'error',
                'action' => 'retry_or_change',
                'fix' => 'Tente novamente ou use outro cartão'
            ],
            
            'cc_rejected_other_reason' => [
                'title' => 'Pagamento recusado',
                'message' => 'O banco emissor recusou o pagamento. Entre em contato com seu banco ou tente outro cartão.',
                'type' => 'error',
                'action' => 'contact_bank',
                'fix' => 'Entre em contato com o banco'
            ],
            
            // 🔄 ESTORNOS
            'refunded' => [
                'title' => 'Pagamento estornado',
                'message' => 'Este pagamento foi estornado. O valor será devolvido à sua conta.',
                'type' => 'info',
                'action' => 'refunded'
            ],
            
            'partially_refunded' => [
                'title' => 'Estorno parcial',
                'message' => 'Parte do valor deste pagamento foi estornado.',
                'type' => 'info',
                'action' => 'refunded'
            ],
            
            'charged_back' => [
                'title' => 'Pagamento contestado',
                'message' => 'Este pagamento foi contestado e estornado.',
                'type' => 'info',
                'action' => 'charged_back'
            ],
            
            // 🚫 CANCELADO
            'cancelled' => [
                'title' => 'Pagamento cancelado',
                'message' => 'Este pagamento foi cancelado.',
                'type' => 'info',
                'action' => 'cancelled'
            ],
            
            // ❓ DESCONHECIDO
            default => [
                'title' => 'Status não identificado',
                'message' => 'Não foi possível processar o pagamento. Entre em contato com o suporte.',
                'type' => 'error',
                'action' => 'contact_support',
                'fix' => 'Entre em contato com o suporte'
            ]
        };
    }
    
    /**
     * Mapeia códigos de erro da API do MercadoPago
     * 
     * @param string|int $errorCode Código do erro retornado
     * @param string|null $errorMessage Mensagem técnica do erro
     * @return array
     */
    public function mapErrorCodeToMessage($errorCode, ?string $errorMessage = null): array
    {
        return match ((string) $errorCode) {
            // Erros de validação de dados
            '205' => [
                'title' => 'Digite o número do seu cartão',
                'message' => 'O número do cartão é obrigatório.',
                'type' => 'error',
                'field' => 'card_number'
            ],
            
            '208' => [
                'title' => 'Escolha um mês',
                'message' => 'Selecione o mês de vencimento do cartão.',
                'type' => 'error',
                'field' => 'expiration_month'
            ],
            
            '209' => [
                'title' => 'Escolha um ano',
                'message' => 'Selecione o ano de vencimento do cartão.',
                'type' => 'error',
                'field' => 'expiration_year'
            ],
            
            '212' => [
                'title' => 'Digite o CPF/CNPJ',
                'message' => 'O documento do titular é obrigatório.',
                'type' => 'error',
                'field' => 'document'
            ],
            
            '213' => [
                'title' => 'Digite o código de segurança',
                'message' => 'O código de segurança (CVV) é obrigatório.',
                'type' => 'error',
                'field' => 'security_code'
            ],
            
            '214' => [
                'title' => 'Digite o CPF/CNPJ',
                'message' => 'O número de documento digitado é inválido.',
                'type' => 'error',
                'field' => 'document'
            ],
            
            '220' => [
                'title' => 'Digite o nome do banco',
                'message' => 'Informe o banco emissor do cartão.',
                'type' => 'error',
                'field' => 'issuer_id'
            ],
            
            '221' => [
                'title' => 'Digite o nome impresso no cartão',
                'message' => 'O nome do titular é obrigatório.',
                'type' => 'error',
                'field' => 'cardholder_name'
            ],
            
            '224' => [
                'title' => 'Código de segurança inválido',
                'message' => 'O código de segurança deve ter 3 ou 4 dígitos.',
                'type' => 'error',
                'field' => 'security_code'
            ],
            
            // Erros de cartão
            '316' => [
                'title' => 'Nome inválido',
                'message' => 'Por favor, digite um nome válido.',
                'type' => 'error',
                'field' => 'cardholder_name'
            ],
            
            '322' => [
                'title' => 'Documento inválido',
                'message' => 'O tipo de documento não é válido.',
                'type' => 'error',
                'field' => 'document_type'
            ],
            
            '323' => [
                'title' => 'CPF/CNPJ inválido',
                'message' => 'Verifique se o documento está correto.',
                'type' => 'error',
                'field' => 'document'
            ],
            
            '324' => [
                'title' => 'Documento inválido',
                'message' => 'O número de documento é inválido.',
                'type' => 'error',
                'field' => 'document'
            ],
            
            '325' => [
                'title' => 'Mês inválido',
                'message' => 'O mês de vencimento está incorreto.',
                'type' => 'error',
                'field' => 'expiration_month'
            ],
            
            '326' => [
                'title' => 'Ano inválido',
                'message' => 'O ano de vencimento está incorreto.',
                'type' => 'error',
                'field' => 'expiration_year'
            ],
            
            // Erro padrão
            default => [
                'title' => 'Erro ao processar pagamento',
                'message' => $errorMessage ?? 'Ocorreu um erro ao processar seu pagamento. Tente novamente.',
                'type' => 'error'
            ]
        };
    }
    
    /**
     * Obtém mensagem amigável baseada no status geral do pagamento
     * 
     * @param string $status Status do pagamento (approved, pending, rejected, etc.)
     * @return array
     */
    public function getStatusMessage(string $status): array
    {
        return match ($status) {
            'approved' => [
                'title' => 'Pagamento aprovado!',
                'message' => 'Seu pagamento foi processado com sucesso.',
                'type' => 'success'
            ],
            
            'pending' => [
                'title' => 'Pagamento pendente',
                'message' => 'Aguardando confirmação do pagamento.',
                'type' => 'warning'
            ],
            
            'in_process' => [
                'title' => 'Pagamento em processamento',
                'message' => 'Seu pagamento está sendo processado.',
                'type' => 'info'
            ],
            
            'rejected' => [
                'title' => 'Pagamento recusado',
                'message' => 'Não foi possível processar seu pagamento.',
                'type' => 'error'
            ],
            
            'cancelled' => [
                'title' => 'Pagamento cancelado',
                'message' => 'Este pagamento foi cancelado.',
                'type' => 'info'
            ],
            
            'refunded' => [
                'title' => 'Pagamento estornado',
                'message' => 'O valor foi devolvido.',
                'type' => 'info'
            ],
            
            'charged_back' => [
                'title' => 'Pagamento contestado',
                'message' => 'Este pagamento foi contestado.',
                'type' => 'warning'
            ],
            
            default => [
                'title' => 'Status desconhecido',
                'message' => 'Status do pagamento não identificado.',
                'type' => 'info'
            ]
        };
    }
    
    /**
     * Verifica se o pagamento pode ser tentado novamente
     * 
     * @param string $statusDetail
     * @return bool
     */
    public function canRetry(string $statusDetail): bool
    {
        $retryableStatuses = [
            'cc_rejected_bad_filled_card_number',
            'cc_rejected_bad_filled_date',
            'cc_rejected_bad_filled_security_code',
            'cc_rejected_bad_filled_other',
            'cc_rejected_card_error',
            'cc_rejected_invalid_installments',
        ];
        
        return in_array($statusDetail, $retryableStatuses);
    }
    
    /**
     * Verifica se deve sugerir mudança de meio de pagamento
     * 
     * @param string $statusDetail
     * @return bool
     */
    public function shouldChangePaymentMethod(string $statusDetail): bool
    {
        $changePaymentStatuses = [
            'cc_rejected_insufficient_amount',
            'cc_rejected_card_disabled',
            'cc_rejected_blacklist',
            'cc_rejected_high_risk',
            'cc_rejected_max_attempts',
            'cc_rejected_duplicated_payment',
        ];
        
        return in_array($statusDetail, $changePaymentStatuses);
    }
}
