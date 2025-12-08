<?php

namespace Tests\Unit\Services\Payment;

use Tests\TestCase;
use App\Services\Payment\MercadoPagoErrorMapper;

class MercadoPagoErrorMapperTest extends TestCase
{
    protected MercadoPagoErrorMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new MercadoPagoErrorMapper();
    }

    /** @test */
    public function it_maps_approved_payment_correctly()
    {
        $result = $this->mapper->mapStatusDetailToMessage('accredited', 'approved');

        $this->assertEquals('Pagamento aprovado!', $result['title']);
        $this->assertEquals('success', $result['type']);
        $this->assertEquals('approved', $result['action']);
        $this->assertStringContainsString('aprovado com sucesso', $result['message']);
    }

    /** @test */
    public function it_maps_invalid_security_code_correctly()
    {
        $result = $this->mapper->mapStatusDetailToMessage('cc_rejected_bad_filled_security_code');

        $this->assertEquals('Código de segurança inválido', $result['title']);
        $this->assertEquals('error', $result['type']);
        $this->assertEquals('retry', $result['action']);
        $this->assertStringContainsString('código de segurança', strtolower($result['message']));
        $this->assertArrayHasKey('fix', $result);
    }

    /** @test */
    public function it_maps_invalid_expiration_date_correctly()
    {
        $result = $this->mapper->mapStatusDetailToMessage('cc_rejected_bad_filled_date');

        $this->assertEquals('Data de vencimento inválida', $result['title']);
        $this->assertEquals('error', $result['type']);
        $this->assertTrue($this->mapper->canRetry('cc_rejected_bad_filled_date'));
    }

    /** @test */
    public function it_maps_insufficient_amount_correctly()
    {
        $result = $this->mapper->mapStatusDetailToMessage('cc_rejected_insufficient_amount');

        $this->assertEquals('Saldo insuficiente', $result['title']);
        $this->assertEquals('error', $result['type']);
        $this->assertEquals('change_payment_method', $result['action']);
        $this->assertTrue($this->mapper->shouldChangePaymentMethod('cc_rejected_insufficient_amount'));
    }

    /** @test */
    public function it_maps_card_disabled_correctly()
    {
        $result = $this->mapper->mapStatusDetailToMessage('cc_rejected_card_disabled');

        $this->assertEquals('Cartão desabilitado', $result['title']);
        $this->assertEquals('error', $result['type']);
        $this->assertTrue($this->mapper->shouldChangePaymentMethod('cc_rejected_card_disabled'));
        $this->assertFalse($this->mapper->canRetry('cc_rejected_card_disabled'));
    }

    /** @test */
    public function it_maps_duplicated_payment_correctly()
    {
        $result = $this->mapper->mapStatusDetailToMessage('cc_rejected_duplicated_payment');

        $this->assertEquals('Pagamento duplicado', $result['title']);
        $this->assertEquals('error', $result['type']);
        $this->assertTrue($this->mapper->shouldChangePaymentMethod('cc_rejected_duplicated_payment'));
    }

    /** @test */
    public function it_maps_invalid_installments_correctly()
    {
        $result = $this->mapper->mapStatusDetailToMessage('cc_rejected_invalid_installments');

        $this->assertEquals('Parcelamento não disponível', $result['title']);
        $this->assertEquals('change_installments', $result['action']);
        $this->assertTrue($this->mapper->canRetry('cc_rejected_invalid_installments'));
    }

    /** @test */
    public function it_maps_call_for_authorize_correctly()
    {
        $result = $this->mapper->mapStatusDetailToMessage('cc_rejected_call_for_authorize');

        $this->assertEquals('Autorização necessária', $result['title']);
        $this->assertEquals('contact_bank', $result['action']);
        $this->assertStringContainsString('banco', strtolower($result['message']));
    }

    /** @test */
    public function it_maps_high_risk_correctly()
    {
        $result = $this->mapper->mapStatusDetailToMessage('cc_rejected_high_risk');

        $this->assertEquals('Pagamento recusado por segurança', $result['title']);
        $this->assertTrue($this->mapper->shouldChangePaymentMethod('cc_rejected_high_risk'));
        $this->assertStringContainsString('PIX', $result['message']);
    }

    /** @test */
    public function it_maps_max_attempts_correctly()
    {
        $result = $this->mapper->mapStatusDetailToMessage('cc_rejected_max_attempts');

        $this->assertEquals('Limite de tentativas excedido', $result['title']);
        $this->assertEquals('wait_or_change', $result['action']);
        $this->assertStringContainsString('tentativas', $result['message']);
    }

    /** @test */
    public function it_maps_blacklist_correctly()
    {
        $result = $this->mapper->mapStatusDetailToMessage('cc_rejected_blacklist');

        $this->assertEquals('Pagamento não processado', $result['title']);
        $this->assertTrue($this->mapper->shouldChangePaymentMethod('cc_rejected_blacklist'));
    }

    /** @test */
    public function it_maps_pending_statuses_correctly()
    {
        $pendingStatuses = [
            'pending_contingency' => 'Pagamento em análise',
            'pending_review_manual' => 'Pagamento em revisão',
            'pending_waiting_payment' => 'Aguardando pagamento',
        ];

        foreach ($pendingStatuses as $status => $expectedTitle) {
            $result = $this->mapper->mapStatusDetailToMessage($status);
            
            $this->assertEquals($expectedTitle, $result['title'], "Failed for status: $status");
            $this->assertContains($result['type'], ['warning', 'info']);
            $this->assertEquals('pending', $result['action']);
        }
    }

    /** @test */
    public function it_maps_refund_statuses_correctly()
    {
        $result = $this->mapper->mapStatusDetailToMessage('refunded');
        
        $this->assertEquals('Pagamento estornado', $result['title']);
        $this->assertEquals('info', $result['type']);
        $this->assertEquals('refunded', $result['action']);
    }

    /** @test */
    public function it_maps_unknown_status_with_default_message()
    {
        $result = $this->mapper->mapStatusDetailToMessage('unknown_status_xyz');

        $this->assertEquals('Status não identificado', $result['title']);
        $this->assertEquals('error', $result['type']);
        $this->assertEquals('contact_support', $result['action']);
    }

    /** @test */
    public function it_maps_error_codes_correctly()
    {
        $errorCodes = [
            '205' => ['title' => 'Digite o número do seu cartão', 'field' => 'card_number'],
            '213' => ['title' => 'Digite o código de segurança', 'field' => 'security_code'],
            '214' => ['title' => 'Digite o CPF/CNPJ', 'field' => 'document'],
            '324' => ['title' => 'Documento inválido', 'field' => 'document'],
        ];

        foreach ($errorCodes as $code => $expected) {
            $result = $this->mapper->mapErrorCodeToMessage($code);
            
            $this->assertEquals($expected['title'], $result['title'], "Failed for error code: $code");
            $this->assertEquals('error', $result['type']);
            
            if (isset($expected['field'])) {
                $this->assertEquals($expected['field'], $result['field'], "Failed field for code: $code");
            }
        }
    }

    /** @test */
    public function it_gets_status_message_correctly()
    {
        $statuses = [
            'approved' => ['title' => 'Pagamento aprovado!', 'type' => 'success'],
            'pending' => ['title' => 'Pagamento pendente', 'type' => 'warning'],
            'rejected' => ['title' => 'Pagamento recusado', 'type' => 'error'],
            'cancelled' => ['title' => 'Pagamento cancelado', 'type' => 'info'],
        ];

        foreach ($statuses as $status => $expected) {
            $result = $this->mapper->getStatusMessage($status);
            
            $this->assertEquals($expected['title'], $result['title']);
            $this->assertEquals($expected['type'], $result['type']);
        }
    }

    /** @test */
    public function can_retry_returns_true_for_retryable_errors()
    {
        $retryableErrors = [
            'cc_rejected_bad_filled_card_number',
            'cc_rejected_bad_filled_date',
            'cc_rejected_bad_filled_security_code',
            'cc_rejected_bad_filled_other',
            'cc_rejected_invalid_installments',
        ];

        foreach ($retryableErrors as $error) {
            $this->assertTrue(
                $this->mapper->canRetry($error),
                "Expected $error to be retryable"
            );
        }
    }

    /** @test */
    public function can_retry_returns_false_for_non_retryable_errors()
    {
        $nonRetryableErrors = [
            'cc_rejected_insufficient_amount',
            'cc_rejected_card_disabled',
            'cc_rejected_blacklist',
            'cc_rejected_high_risk',
        ];

        foreach ($nonRetryableErrors as $error) {
            $this->assertFalse(
                $this->mapper->canRetry($error),
                "Expected $error to NOT be retryable"
            );
        }
    }

    /** @test */
    public function should_change_payment_method_returns_true_for_specific_errors()
    {
        $changePaymentErrors = [
            'cc_rejected_insufficient_amount',
            'cc_rejected_card_disabled',
            'cc_rejected_blacklist',
            'cc_rejected_high_risk',
            'cc_rejected_duplicated_payment',
        ];

        foreach ($changePaymentErrors as $error) {
            $this->assertTrue(
                $this->mapper->shouldChangePaymentMethod($error),
                "Expected $error to suggest payment method change"
            );
        }
    }

    /** @test */
    public function all_test_card_scenarios_have_proper_mappings()
    {
        // Baseado na documentação dos cartões de teste do MercadoPago
        $testScenarios = [
            'cc_rejected_bad_filled_security_code' => 'SECU', // CVV inválido
            'cc_rejected_bad_filled_date' => 'EXPI', // Data vencimento
            'cc_rejected_bad_filled_other' => 'FORM', // Erro formulário
            'cc_rejected_insufficient_amount' => 'FUND', // Saldo insuficiente
            'cc_rejected_other_reason' => 'OTHE', // Erro geral
            'cc_rejected_call_for_authorize' => 'CALL', // Autorização
            'cc_rejected_invalid_installments' => 'INST', // Parcelas
            'cc_rejected_duplicated_payment' => 'DUPL', // Duplicado
            'cc_rejected_card_disabled' => 'LOCK', // Cartão bloqueado
            'cc_rejected_blacklist' => 'BLAC', // Lista negra
        ];

        foreach ($testScenarios as $statusDetail => $cardName) {
            $result = $this->mapper->mapStatusDetailToMessage($statusDetail);
            
            $this->assertNotEmpty($result['title'], "Missing title for $cardName ($statusDetail)");
            $this->assertNotEmpty($result['message'], "Missing message for $cardName ($statusDetail)");
            $this->assertContains($result['type'], ['success', 'error', 'warning', 'info']);
            $this->assertArrayHasKey('action', $result);
        }
    }
}
