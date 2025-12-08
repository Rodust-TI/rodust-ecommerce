<?php

namespace App\Services\Bling;

use App\Services\ERP\BlingV3Adapter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service: Gerenciamento de Status do Bling
 * 
 * Responsável por:
 * - Descobrir o ID do módulo de Vendas
 * - Obter lista de situações (status) do módulo de Vendas
 * - Mapear IDs de situação para nomes legíveis
 * - Cachear dados para evitar requisições desnecessárias
 */
class BlingStatusService
{
    private const CACHE_KEY_SALES_MODULE_ID = 'bling_sales_module_id';
    private const CACHE_KEY_STATUS_LIST = 'bling_status_list';
    private const CACHE_TTL_HOURS = 24; // Cache por 24 horas

    public function __construct(
        private BlingV3Adapter $bling
    ) {}

    /**
     * Obter ID do módulo de Vendas
     * 
     * @return int|null ID do módulo ou null se não encontrado
     */
    public function getSalesModuleId(): ?int
    {
        // Verificar cache primeiro
        $cachedId = Cache::get(self::CACHE_KEY_SALES_MODULE_ID);
        if ($cachedId) {
            Log::debug('Bling Sales Module ID obtido do cache', ['id' => $cachedId]);
            return $cachedId;
        }

        try {
            Log::info('Buscando módulos do Bling para encontrar ID de Vendas');
            
            $modules = $this->bling->getModules();
            
            Log::info('Módulos retornados do Bling', [
                'count' => count($modules),
                'modules' => $modules
            ]);
            
            // Procurar pelo módulo de Vendas
            foreach ($modules as $module) {
                Log::debug('Verificando módulo', [
                    'module' => $module,
                    'nome' => $module['nome'] ?? 'N/A',
                    'id' => $module['id'] ?? 'N/A'
                ]);
                
                // O nome pode ser "Vendas" ou similar
                if (stripos($module['nome'] ?? '', 'venda') !== false) {
                    $moduleId = $module['id'];
                    
                    // Cachear por 24 horas (ID de módulo não muda frequentemente)
                    Cache::put(self::CACHE_KEY_SALES_MODULE_ID, $moduleId, now()->addHours(self::CACHE_TTL_HOURS));
                    
                    Log::info('Módulo de Vendas encontrado', [
                        'id' => $moduleId,
                        'nome' => $module['nome'] ?? 'N/A'
                    ]);
                    
                    return $moduleId;
                }
            }

            Log::warning('Módulo de Vendas não encontrado na lista de módulos', [
                'modules' => $modules
            ]);
            
            return null;

        } catch (\Exception $e) {
            Log::error('Erro ao buscar módulos do Bling', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Obter lista de situações (status) do módulo de Vendas
     * 
     * @return array Lista de situações [id => nome]
     */
    public function getSalesStatuses(): array
    {
        // Verificar cache primeiro
        $cachedStatuses = Cache::get(self::CACHE_KEY_STATUS_LIST);
        if ($cachedStatuses) {
            Log::debug('Status do Bling obtidos do cache', ['count' => count($cachedStatuses)]);
            return $cachedStatuses;
        }

        try {
            // Primeiro obter o ID do módulo de Vendas
            $moduleId = $this->getSalesModuleId();
            
            if (!$moduleId) {
                Log::error('Não foi possível obter ID do módulo de Vendas');
                return $this->getDefaultStatusMapping();
            }

            Log::info('Buscando situações do módulo de Vendas', ['module_id' => $moduleId]);
            
            $statuses = $this->bling->getSituations($moduleId);
            
            // Se a API retornou vazio ou falhou, usar mapeamento padrão
            if (empty($statuses)) {
                Log::warning('API /situacoes do Bling retornou vazio. Usando mapeamento padrão.');
                return $this->getDefaultStatusMapping();
            }
            
            // Transformar array de situações em formato [id => nome]
            $statusMap = [];
            foreach ($statuses as $status) {
                $statusMap[$status['id']] = [
                    'nome' => $status['nome'] ?? 'Desconhecido',
                    'cor' => $status['cor'] ?? null,
                    'herdado' => $status['herdado'] ?? false,
                ];
            }

            // Cachear por 24 horas
            Cache::put(self::CACHE_KEY_STATUS_LIST, $statusMap, now()->addHours(self::CACHE_TTL_HOURS));
            
            Log::info('Situações do Bling carregadas com sucesso', [
                'count' => count($statusMap),
                'statuses' => $statusMap
            ]);

            return $statusMap;

        } catch (\Exception $e) {
            Log::error('Erro ao buscar situações do Bling', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Retornar mapeamento padrão em caso de erro
            return $this->getDefaultStatusMapping();
        }
    }

    /**
     * Obter nome da situação pelo ID
     * 
     * @param int $statusId ID da situação no Bling
     * @return string Nome da situação
     */
    public function getStatusName(int $statusId): string
    {
        $statuses = $this->getSalesStatuses();
        return $statuses[$statusId]['nome'] ?? 'Desconhecido';
    }

    /**
     * Mapear situação do Bling para status interno
     * 
     * @param array $blingStatus Dados da situação do Bling ['id' => int, 'valor' => int]
     * @return string Status interno normalizado
     */
    public function mapBlingStatusToInternal(array $blingStatus): string
    {
        $statusId = $blingStatus['id'] ?? null;
        
        if (!$statusId) {
            return 'pending';
        }

        // Mapeamento direto por ID (prioridade alta - para IDs conhecidos)
        $directMapping = $this->getDirectStatusMapping();
        if (isset($directMapping[$statusId])) {
            return $directMapping[$statusId];
        }

        // Fallback: mapear por nome
        $statusName = $this->getStatusName($statusId);
        
        // Mapear nome da situação para status interno
        return $this->normalizeStatusName($statusName);
    }

    /**
     * Mapeamento direto por ID do Bling
     * 
     * IDs conhecidos que devem ser mapeados diretamente
     * (independente do nome que o usuário deu no Bling)
     * 
     * @return array [id => status_interno]
     */
    protected function getDirectStatusMapping(): array
    {
        return [
            9 => 'delivered',  // Atendido = Pedido concluído/entregue
            12 => 'cancelled', // Cancelado
            // IDs padrão do Bling (podem variar, mas são comuns)
            6 => 'pending',    // Em aberto
            15 => 'processing', // Em andamento
        ];
    }

    /**
     * Normalizar nome de status do Bling para status interno
     * 
     * @param string $blingStatusName Nome do status no Bling
     * @return string Status interno
     */
    protected function normalizeStatusName(string $blingStatusName): string
    {
        $normalized = strtolower(trim($blingStatusName));
        
        return match(true) {
            str_contains($normalized, 'aberto') => 'pending',
            str_contains($normalized, 'pendente') => 'pending',
            str_contains($normalized, 'aguardando') => 'pending',
            str_contains($normalized, 'andamento') => 'processing',
            str_contains($normalized, 'processando') => 'processing',
            str_contains($normalized, 'faturado') => 'invoiced',
            str_contains($normalized, 'faturamento') => 'invoiced',
            str_contains($normalized, 'enviado') => 'shipped',
            str_contains($normalized, 'envio') => 'shipped',
            str_contains($normalized, 'transporte') => 'shipped',
            str_contains($normalized, 'atendido') => 'delivered', // Atendido = Pedido concluído/entregue
            str_contains($normalized, 'concluído') => 'delivered',
            str_contains($normalized, 'finalizado') => 'delivered',
            str_contains($normalized, 'entregue') => 'delivered',
            str_contains($normalized, 'entrega') => 'delivered',
            str_contains($normalized, 'cancelado') => 'cancelled',
            str_contains($normalized, 'cancelamento') => 'cancelled',
            default => 'pending',
        };
    }

    /**
     * Mapeamento padrão de status (fallback)
     * 
     * @return array
     */
    protected function getDefaultStatusMapping(): array
    {
        return [
            // IDs fictícios - serão substituídos pelos reais quando possível
            0 => ['nome' => 'Em aberto', 'cor' => null, 'herdado' => false],
            1 => ['nome' => 'Em andamento', 'cor' => null, 'herdado' => false],
            2 => ['nome' => 'Faturado', 'cor' => null, 'herdado' => false],
            3 => ['nome' => 'Enviado', 'cor' => null, 'herdado' => false],
            4 => ['nome' => 'Entregue', 'cor' => null, 'herdado' => false],
            5 => ['nome' => 'Cancelado', 'cor' => null, 'herdado' => false],
        ];
    }

    /**
     * Limpar cache de status
     * Útil quando houver mudanças nas configurações do Bling
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY_SALES_MODULE_ID);
        Cache::forget(self::CACHE_KEY_STATUS_LIST);
        
        Log::info('Cache de status do Bling limpo');
    }

    /**
     * Obter detalhes completos de um status pelo ID
     * 
     * @param int $statusId
     * @return array|null
     */
    public function getStatusDetails(int $statusId): ?array
    {
        $statuses = $this->getSalesStatuses();
        return $statuses[$statusId] ?? null;
    }

    /**
     * Buscar ID de status pelo nome (case-insensitive, busca parcial)
     * 
     * Útil para encontrar IDs de status customizados criados no Bling
     * Ex: buscar "Faturado" ou "Enviado" e retornar o ID que o Bling atribuiu
     * 
     * @param string $statusName Nome do status (ex: "Faturado", "Enviado")
     * @return int|null ID do status ou null se não encontrado
     */
    public function findStatusIdByName(string $statusName): ?int
    {
        $statuses = $this->getSalesStatuses();
        $searchName = strtolower(trim($statusName));
        
        foreach ($statuses as $id => $status) {
            $statusNome = strtolower(trim($status['nome'] ?? ''));
            
            // Busca exata ou parcial
            if ($statusNome === $searchName || str_contains($statusNome, $searchName)) {
                return $id;
            }
        }
        
        return null;
    }

    /**
     * Buscar ID de status por múltiplos nomes possíveis
     * 
     * @param array $possibleNames Array de nomes possíveis (ex: ['Faturado', 'Faturamento'])
     * @return int|null ID do primeiro status encontrado
     */
    public function findStatusIdByNames(array $possibleNames): ?int
    {
        foreach ($possibleNames as $name) {
            $id = $this->findStatusIdByName($name);
            if ($id !== null) {
                return $id;
            }
        }
        
        return null;
    }
}
