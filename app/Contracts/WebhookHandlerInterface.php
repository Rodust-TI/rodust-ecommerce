<?php

namespace App\Contracts;

use App\Models\WebhookLog;

/**
 * Interface para handlers de webhook
 * 
 * Permite trocar de fornecedor (Bling, Tiny, etc) sem afetar o código
 */
interface WebhookHandlerInterface
{
    /**
     * Processar webhook recebido
     * 
     * @param array $data Dados do webhook (payload)
     * @param string $action Ação (created, updated, deleted)
     * @param WebhookLog $webhookLog Log do webhook para atualizar metadata
     * @return void
     */
    public function handle(array $data, string $action, WebhookLog $webhookLog): void;
}

