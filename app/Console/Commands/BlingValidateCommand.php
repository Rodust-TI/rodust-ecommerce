<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

/**
 * Comando para executar validação do Bling (Desafio de Homologação)
 * 
 * Executa os 5 passos sequenciais exigidos pelo Bling para validar
 * a integração, respeitando os limites de tempo e headers.
 */
class BlingValidateCommand extends Command
{
    protected $signature = 'bling:validate 
                            {--token= : Access token do Bling (opcional, usa cache se omitido)}';

    protected $description = 'Executa o desafio de validação da API Bling v3';

    protected Client $client;
    protected string $baseUrl = 'https://api.bling.com.br/Api/v3';
    protected ?string $homologationHash = null;

    public function handle(): int
    {
        $this->info('🚀 Iniciando validação Bling API v3...');
        $this->newLine();

        $token = $this->option('token') ?? Cache::get('bling_access_token');

        if (!$token) {
            $this->error('❌ Access token não encontrado. Use --token=SEU_TOKEN');
            return Command::FAILURE;
        }

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 5,
            'headers' => [
                'Authorization' => "Bearer {$token}",
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $startTime = microtime(true);

        try {
            // Passo 1: GET - Obter dados do produto
            $productData = $this->step1GetProduct();
            $this->success('✓ Passo 1: Produto obtido');

            // Passo 2: POST - Criar produto
            $productId = $this->step2CreateProduct($productData);
            $this->success("✓ Passo 2: Produto criado (ID: {$productId})");

            // Passo 3: PUT - Atualizar produto
            $this->step3UpdateProduct($productId, $productData);
            $this->success('✓ Passo 3: Produto atualizado');

            // Passo 4: PATCH - Alterar situação
            $this->step4PatchSituation($productId);
            $this->success('✓ Passo 4: Situação alterada');

            // Passo 5: DELETE - Remover produto
            $this->step5DeleteProduct($productId);
            $this->success('✓ Passo 5: Produto deletado');

            $totalTime = round((microtime(true) - $startTime), 2);
            
            $this->newLine();
            $this->info("⏱️  Tempo total: {$totalTime}s");
            
            if ($totalTime > 10) {
                $this->warn('⚠️  Tempo excedeu 10 segundos!');
                return Command::FAILURE;
            }

            $this->newLine();
            $this->info('🎉 Validação concluída com sucesso!');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Erro: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Passo 1: GET - Obter dados do produto de teste
     */
    protected function step1GetProduct(): array
    {
        $this->line('📥 Passo 1: Obtendo dados do produto...');
        
        $response = $this->client->get('/homologacao/produtos', [
            'headers' => $this->getHomologationHeaders(),
        ]);

        $this->updateHomologationHash($response);
        
        $data = json_decode($response->getBody()->getContents(), true);
        
        $this->table(
            ['Campo', 'Valor'],
            [
                ['Nome', $data['data']['nome']],
                ['Preço', 'R$ ' . number_format($data['data']['preco'], 2, ',', '.')],
                ['Código', $data['data']['codigo']],
            ]
        );

        sleep(2); // Limite entre requisições
        
        return $data['data'];
    }

    /**
     * Passo 2: POST - Criar produto
     */
    protected function step2CreateProduct(array $productData): int
    {
        $this->line('📤 Passo 2: Criando produto...');
        
        $response = $this->client->post('/homologacao/produtos', [
            'json' => $productData,
            'headers' => $this->getHomologationHeaders(),
        ]);

        $this->updateHomologationHash($response);
        
        $data = json_decode($response->getBody()->getContents(), true);
        
        sleep(2);
        
        return $data['data']['id'];
    }

    /**
     * Passo 3: PUT - Atualizar nome do produto
     */
    protected function step3UpdateProduct(int $productId, array $productData): void
    {
        $this->line('✏️  Passo 3: Atualizando produto...');
        
        $productData['nome'] = 'Copo'; // Alterar nome conforme especificação
        
        $response = $this->client->put("/homologacao/produtos/{$productId}", [
            'json' => $productData,
            'headers' => $this->getHomologationHeaders(),
        ]);

        $this->updateHomologationHash($response);
        
        sleep(2);
    }

    /**
     * Passo 4: PATCH - Alterar situação para Inativo
     */
    protected function step4PatchSituation(int $productId): void
    {
        $this->line('🔄 Passo 4: Alterando situação...');
        
        $response = $this->client->patch("/homologacao/produtos/{$productId}/situacoes", [
            'json' => ['situacao' => 'I'],
            'headers' => $this->getHomologationHeaders(),
        ]);

        $this->updateHomologationHash($response);
        
        sleep(2);
    }

    /**
     * Passo 5: DELETE - Remover produto
     */
    protected function step5DeleteProduct(int $productId): void
    {
        $this->line('🗑️  Passo 5: Deletando produto...');
        
        $response = $this->client->delete("/homologacao/produtos/{$productId}", [
            'headers' => $this->getHomologationHeaders(),
        ]);

        $this->updateHomologationHash($response);
    }

    /**
     * Obter headers com hash de homologação
     */
    protected function getHomologationHeaders(): array
    {
        if (!$this->homologationHash) {
            return [];
        }

        return ['x-bling-homologacao' => $this->homologationHash];
    }

    /**
     * Atualizar hash de homologação do header da resposta
     */
    protected function updateHomologationHash($response): void
    {
        $headers = $response->getHeaders();
        
        if (isset($headers['x-bling-homologacao'][0])) {
            $this->homologationHash = $headers['x-bling-homologacao'][0];
            $this->line("   Hash: {$this->homologationHash}");
        }
    }

    /**
     * Mensagem de sucesso formatada
     */
    protected function success(string $message): void
    {
        $this->line("<fg=green>{$message}</>");
    }
}
