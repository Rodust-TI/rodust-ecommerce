<?php

namespace App\Services;

use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Exceptions\MPApiException;
use Illuminate\Support\Facades\Log;
use App\Helpers\IntegrationHelper;
use App\Services\Payment\MercadoPagoErrorMapper;
use App\Exceptions\MercadoPagoException;

class MercadoPagoService
{
    protected $client;
    protected $accessToken;
    protected $publicKey;
    protected MercadoPagoErrorMapper $errorMapper;

    public function __construct(MercadoPagoErrorMapper $errorMapper)
    {
        $this->errorMapper = $errorMapper;
        
        // Obter credenciais baseado no modo (sandbox/production)
        $credentials = IntegrationHelper::getMercadoPagoCredentials();
        
        $this->accessToken = $credentials['access_token'];
        $this->publicKey = $credentials['public_key'];
        
        // Configurar SDK
        MercadoPagoConfig::setAccessToken($this->accessToken);
        MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);
        
        $this->client = new PaymentClient();
    }

    /**
     * Criar pagamento PIX
     */
    public function createPixPayment($order, $customerData)
    {
        try {
            $paymentData = [
                'transaction_amount' => (float) $order->total,
                'description' => "Pedido #{$order->id} - Rodust.com.br",
                'payment_method_id' => 'pix',
                'payer' => [
                    'email' => $customerData['email'],
                    'first_name' => $this->getFirstName($customerData['name']),
                    'last_name' => $this->getLastName($customerData['name']),
                    'identification' => [
                        'type' => $this->getDocumentType($customerData['document']),
                        'number' => preg_replace('/\D/', '', $customerData['document'])
                    ]
                ],
                // 'notification_url' => config('services.mercadopago.webhook_url'), // Comentado: localhost não é aceito pelo Mercado Pago
                'external_reference' => (string) $order->id,
                'metadata' => [
                    'order_id' => $order->id,
                    'customer_id' => $order->customer_id
                ]
            ];

            Log::info('Criando pagamento PIX no Mercado Pago', $paymentData);

            $payment = $this->client->create($paymentData);

            Log::info('Pagamento PIX criado', [
                'payment_id' => $payment->id,
                'status' => $payment->status,
                'qr_code' => isset($payment->point_of_interaction->transaction_data->qr_code)
            ]);

            return [
                'success' => true,
                'payment_id' => $payment->id,
                'status' => $payment->status,
                'qr_code' => $payment->point_of_interaction->transaction_data->qr_code ?? null,
                'qr_code_base64' => $payment->point_of_interaction->transaction_data->qr_code_base64 ?? null,
                'ticket_url' => $payment->point_of_interaction->transaction_data->ticket_url ?? null,
                'external_resource_url' => $payment->external_resource_url ?? null
            ];

        } catch (MPApiException $e) {
            Log::error('Erro API Mercado Pago (PIX)', [
                'status' => $e->getApiResponse()->getStatusCode(),
                'content' => $e->getApiResponse()->getContent()
            ]);
            
            throw MercadoPagoException::paymentFailed(
                $e->getMessage(),
                [
                    'status_code' => $e->getApiResponse()->getStatusCode(),
                    'response' => $e->getApiResponse()->getContent(),
                    'payment_method' => 'pix'
                ]
            );
        } catch (\Exception $e) {
            Log::error('Erro ao criar pagamento PIX', ['error' => $e->getMessage()]);
            
            throw MercadoPagoException::apiError(
                'Erro inesperado ao processar pagamento PIX: ' . $e->getMessage()
            );
        }
    }

    /**
     * Criar pagamento com Boleto
     */
    public function createBoletoPayment($order, $customerData)
    {
        try {
            $paymentData = [
                'transaction_amount' => (float) $order->total,
                'description' => "Pedido #{$order->id} - Rodust.com.br",
                'payment_method_id' => 'bolbradesco', // ou 'pec' para Boleto Parcelado
                'payer' => [
                    'email' => $customerData['email'],
                    'first_name' => $this->getFirstName($customerData['name']),
                    'last_name' => $this->getLastName($customerData['name']),
                    'identification' => [
                        'type' => $this->getDocumentType($customerData['document']),
                        'number' => preg_replace('/\D/', '', $customerData['document'])
                    ],
                    'address' => [
                        'zip_code' => preg_replace('/\D/', '', $order->shipping_zipcode),
                        'street_name' => $order->shipping_address,
                        'street_number' => $order->shipping_number,
                        'neighborhood' => $order->shipping_neighborhood,
                        'city' => $order->shipping_city,
                        'federal_unit' => $order->shipping_state
                    ]
                ],
                // 'notification_url' => config('urls.integrations.mercadopago.webhook_url'), // Comentado: localhost não é aceito pelo Mercado Pago
                'external_reference' => (string) $order->id,
                'metadata' => [
                    'order_id' => $order->id,
                    'customer_id' => $order->customer_id
                ]
            ];

            Log::info('Criando boleto no Mercado Pago', $paymentData);

            $payment = $this->client->create($paymentData);

            Log::info('Boleto criado', [
                'payment_id' => $payment->id,
                'status' => $payment->status
            ]);

            return [
                'success' => true,
                'payment_id' => $payment->id,
                'status' => $payment->status,
                'ticket_url' => $payment->transaction_details->external_resource_url ?? null,
                'barcode' => $payment->barcode->content ?? null,
                'due_date' => $payment->date_of_expiration ?? null
            ];

        } catch (MPApiException $e) {
            Log::error('Erro API Mercado Pago (Boleto)', [
                'status' => $e->getApiResponse()->getStatusCode(),
                'content' => $e->getApiResponse()->getContent()
            ]);
            
            throw MercadoPagoException::paymentFailed(
                'Erro ao gerar boleto: ' . $e->getMessage(),
                [
                    'status_code' => $e->getApiResponse()->getStatusCode(),
                    'response' => $e->getApiResponse()->getContent(),
                    'payment_method' => 'boleto'
                ]
            );
        } catch (\Exception $e) {
            Log::error('Erro ao criar boleto', ['error' => $e->getMessage()]);
            
            throw MercadoPagoException::apiError(
                'Erro inesperado ao processar boleto: ' . $e->getMessage()
            );
        }
    }

    /**
     * Criar pagamento com Cartão de Crédito
     */
    public function createCardPayment($order, $customerData, $cardData)
    {
        try {
            $paymentData = [
                'transaction_amount' => (float) $order->total,
                'token' => $cardData['token'], // Token gerado pelo Mercado Pago JS
                'description' => "Pedido #{$order->id} - Rodust.com.br",
                'installments' => (int) $cardData['installments'],
                'payment_method_id' => $cardData['payment_method_id'],
                'issuer_id' => $cardData['issuer_id'],
                'payer' => [
                    'email' => $customerData['email'],
                    'identification' => [
                        'type' => $this->getDocumentType($customerData['document']),
                        'number' => preg_replace('/\D/', '', $customerData['document'])
                    ]
                ],
                // 'notification_url' => config('urls.integrations.mercadopago.webhook_url'), // Comentado: localhost não é aceito pelo Mercado Pago
                'external_reference' => (string) $order->id,
                'metadata' => [
                    'order_id' => $order->id,
                    'customer_id' => $order->customer_id
                ]
            ];

            Log::info('Criando pagamento com cartão', [
                'order_id' => $order->id,
                'installments' => $cardData['installments']
            ]);

            $payment = $this->client->create($paymentData);

            Log::info('Pagamento com cartão processado', [
                'payment_id' => $payment->id,
                'status' => $payment->status,
                'status_detail' => $payment->status_detail
            ]);

            // Mapear mensagem amigável
            $messageData = $this->errorMapper->mapStatusDetailToMessage(
                $payment->status_detail,
                $payment->status
            );

            return [
                'success' => true,
                'payment_id' => $payment->id,
                'status' => $payment->status,
                'status_detail' => $payment->status_detail,
                'approved' => $payment->status === 'approved',
                'message' => $messageData['message'],
                'title' => $messageData['title'],
                'message_type' => $messageData['type'],
                'can_retry' => $this->errorMapper->canRetry($payment->status_detail),
                'should_change_payment' => $this->errorMapper->shouldChangePaymentMethod($payment->status_detail)
            ];

        } catch (MPApiException $e) {
            Log::error('Erro API Mercado Pago (Cartão)', [
                'status' => $e->getApiResponse()->getStatusCode(),
                'content' => $e->getApiResponse()->getContent()
            ]);
            
            // Tentar extrair detalhes do erro da API
            $content = $e->getApiResponse()->getContent();
            $errorCode = $content['cause'][0]['code'] ?? null;
            $errorMessage = $content['message'] ?? null;
            
            $errorData = $errorCode 
                ? $this->errorMapper->mapErrorCodeToMessage($errorCode, $errorMessage)
                : ['title' => 'Erro ao processar pagamento', 'message' => $e->getMessage(), 'type' => 'error'];
            
            // Se tem campo específico, usar invalidCardData
            if (isset($errorData['field'])) {
                throw MercadoPagoException::invalidCardData(
                    $errorData['field'],
                    $errorData['message']
                );
            }
            
            // Usar método específico que integra com ErrorMapper
            throw MercadoPagoException::paymentFailedWithErrorMapper(
                $errorData,
                $e->getApiResponse()->getStatusCode(),
                $content
            );
        } catch (\Exception $e) {
            Log::error('Erro ao processar cartão', ['error' => $e->getMessage()]);
            
            throw MercadoPagoException::apiError(
                'Erro inesperado ao processar seu pagamento. Por favor, tente novamente.'
            );
        }
    }

    /**
     * Consultar status do pagamento
     */
    public function getPaymentStatus($paymentId)
    {
        try {
            // Se for ID simulado (começa com "sim_"), retornar status simulado
            if (is_string($paymentId) && str_starts_with($paymentId, 'sim_')) {
                Log::info('🧪 Retornando status simulado para payment_id', ['payment_id' => $paymentId]);
                
                return [
                    'success' => true,
                    'status' => 'approved',
                    'status_detail' => 'accredited',
                    'approved' => true
                ];
            }
            
            // Se estiver em desenvolvimento e não for ID numérico válido, simular
            if (config('app.env') !== 'production' && !is_numeric($paymentId)) {
                Log::info('🧪 ID de pagamento inválido em dev - simulando aprovação', ['payment_id' => $paymentId]);
                
                return [
                    'success' => true,
                    'status' => 'approved',
                    'status_detail' => 'accredited',
                    'approved' => true
                ];
            }
            
            $payment = $this->client->get((int) $paymentId);
            
            return [
                'success' => true,
                'status' => $payment->status,
                'status_detail' => $payment->status_detail,
                'approved' => $payment->status === 'approved',
                // Taxas do Mercado Pago
                'transaction_amount' => $payment->transaction_amount ?? null,
                'fee_details' => $payment->fee_details ?? [],
                'transaction_details' => [
                    'net_received_amount' => $payment->transaction_details->net_received_amount ?? null,
                    'total_paid_amount' => $payment->transaction_details->total_paid_amount ?? null,
                    'installment_amount' => $payment->transaction_details->installment_amount ?? null,
                ],
                // Dados do pagamento
                'payment_method_id' => $payment->payment_method_id ?? null,
                'payment_type_id' => $payment->payment_type_id ?? null,
                'installments' => $payment->installments ?? 1,
            ];
        } catch (\Exception $e) {
            Log::error('Erro ao consultar pagamento', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);
            
            // Em desenvolvimento, simular aprovação se falhar a consulta
            if (config('app.env') !== 'production') {
                Log::warning('🧪 Erro ao consultar pagamento em dev - simulando aprovação');
                
                return [
                    'success' => true,
                    'status' => 'approved',
                    'status_detail' => 'accredited',
                    'approved' => true
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Erro ao consultar pagamento'
            ];
        }
    }

    /**
     * Helpers
     */
    private function getFirstName($fullName)
    {
        $parts = explode(' ', trim($fullName));
        return $parts[0] ?? 'Cliente';
    }

    private function getLastName($fullName)
    {
        $parts = explode(' ', trim($fullName));
        array_shift($parts);
        return implode(' ', $parts) ?: 'Rodust';
    }

    private function getDocumentType($document)
    {
        $clean = preg_replace('/\D/', '', $document);
        return strlen($clean) === 11 ? 'CPF' : 'CNPJ';
    }

    /**
     * Obter Public Key para o frontend
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }
}
