<?php

namespace App\Contracts;

/**
 * Interface para integração com ERPs
 * 
 * Abstrai a comunicação com sistemas de gestão empresarial (ERP),
 * permitindo trocar de fornecedor (Bling, Tiny, Omie, etc) sem afetar o código.
 */
interface ERPInterface
{
    /**
     * Buscar produtos do ERP
     * 
     * @param array $filters Filtros de busca (página, limite, busca, etc)
     * @return array Array de produtos normalizados
     */
    public function getProducts(array $filters = []): array;

    /**
     * Buscar um produto específico
     * 
     * @param string $erpId ID do produto no ERP
     * @return array|null Dados do produto ou null se não encontrado
     */
    public function getProduct(string $erpId): ?array;

    /**
     * Criar produto no ERP
     * 
     * @param array $productData Dados do produto normalizados
     * @return string|null ID do produto criado no ERP
     */
    public function createProduct(array $productData): ?string;

    /**
     * Atualizar produto no ERP
     * 
     * @param string $erpId ID do produto no ERP
     * @param array $productData Dados atualizados normalizados
     * @return bool Sucesso da operação
     */
    public function updateProduct(string $erpId, array $productData): bool;

    /**
     * Deletar produto no ERP
     * 
     * @param string $erpId ID do produto no ERP
     * @return bool Sucesso da operação
     */
    public function deleteProduct(string $erpId): bool;

    /**
     * Criar pedido no ERP
     * 
     * @param array $orderData Dados do pedido normalizados
     * @return string|null ID ou número do pedido no ERP
     */
    public function createOrder(array $orderData): ?string;

    /**
     * Atualizar estoque de um produto
     * 
     * @param string $erpId ID do produto no ERP
     * @param int $quantity Nova quantidade em estoque
     * @return bool Sucesso da operação
     */
    public function updateStock(string $erpId, int $quantity): bool;

    /**
     * Buscar situações/status disponíveis no ERP
     * 
     * @return array Array de situações (id, nome, tipo)
     */
    public function getStatuses(): array;

    /**
     * Verificar se conexão com ERP está funcionando
     * 
     * @return bool True se conectado com sucesso
     */
    public function testConnection(): bool;
}
