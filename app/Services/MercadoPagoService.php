<?php

namespace App\Services;

use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Exceptions\MPApiException;
use Illuminate\Support\Facades\Log;

class MercadoPagoService
{
    protected $client;
    protected $accessToken;
    protected $publicKey;

    public function __construct()
    {
        $this->accessToken = config('services.mercadopago.access_token');
        $this->publicKey = config('services.mercadopago.public_key');
        
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
            
            return [
                'success' => false,
                'error' => 'Erro ao gerar pagamento PIX: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            Log::error('Erro ao criar pagamento PIX', ['error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'error' => 'Erro inesperado ao processar pagamento'
            ];
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
                // 'notification_url' => config('services.mercadopago.webhook_url'), // Comentado: localhost não é aceito pelo Mercado Pago
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
            
            return [
                'success' => false,
                'error' => 'Erro ao gerar boleto: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            Log::error('Erro ao criar boleto', ['error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'error' => 'Erro inesperado ao processar boleto'
            ];
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
                // 'notification_url' => config('services.mercadopago.webhook_url'), // Comentado: localhost não é aceito pelo Mercado Pago
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

            return [
                'success' => true,
                'payment_id' => $payment->id,
                'status' => $payment->status,
                'status_detail' => $payment->status_detail,
                'approved' => $payment->status === 'approved'
            ];

        } catch (MPApiException $e) {
            Log::error('Erro API Mercado Pago (Cartão)', [
                'status' => $e->getApiResponse()->getStatusCode(),
                'content' => $e->getApiResponse()->getContent()
            ]);
            
            return [
                'success' => false,
                'error' => 'Erro ao processar cartão: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            Log::error('Erro ao processar cartão', ['error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'error' => 'Erro inesperado ao processar cartão'
            ];
        }
    }

    /**
     * Consultar status do pagamento
     */
    public function getPaymentStatus($paymentId)
    {
        try {
            $payment = $this->client->get($paymentId);
            
            return [
                'success' => true,
                'status' => $payment->status,
                'status_detail' => $payment->status_detail,
                'approved' => $payment->status === 'approved'
            ];
        } catch (\Exception $e) {
            Log::error('Erro ao consultar pagamento', ['error' => $e->getMessage()]);
            
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
