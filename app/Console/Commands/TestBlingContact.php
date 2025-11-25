<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BlingCustomerService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class TestBlingContact extends Command
{
    protected $signature = 'bling:test-contact {action} {id?}';
    protected $description = 'Testar operações na API de contatos do Bling';

    public function handle()
    {
        $action = $this->argument('action');
        $contactId = $this->argument('id');

        $token = $this->getAccessToken();

        if (!$token) {
            $this->error('Falha ao obter token de acesso');
            return 1;
        }

        switch ($action) {
            case 'get':
                $this->getContact($token, $contactId);
                break;
            case 'update-pf':
                $this->updateContactPF($token, $contactId);
                break;
            case 'update-pj':
                $this->updateContactPJ($token, $contactId);
                break;
            case 'list':
                $this->listContacts($token);
                break;
            default:
                $this->error('Ação inválida. Use: get, update-pf, update-pj, list');
                return 1;
        }

        return 0;
    }

    private function getAccessToken(): ?string
    {
        // Tentar pegar token do cache
        $token = Cache::get('bling_access_token');
        
        if ($token) {
            return $token;
        }

        // Se não existe, gerar novo
        $clientId = config('services.bling.client_id');
        $clientSecret = config('services.bling.client_secret');

        $response = Http::asForm()->post('https://www.bling.com.br/Api/v3/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $token = $data['access_token'];
            $expiresIn = $data['expires_in'] ?? 3600;
            
            Cache::put('bling_access_token', $token, now()->addSeconds($expiresIn - 60));
            
            return $token;
        }

        return null;
    }

    private function getContact($token, $contactId)
    {
        if (!$contactId) {
            $this->error('ID do contato é obrigatório');
            return;
        }

        $response = Http::withToken($token)
            ->get("https://www.bling.com.br/Api/v3/contatos/{$contactId}");

        $this->info('Status: ' . $response->status());
        $this->line(json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function updateContactPF($token, $contactId)
    {
        if (!$contactId) {
            $this->error('ID do contato é obrigatório');
            return;
        }

        $payload = [
            'nome' => 'Aureo Teste PF',
            'codigo' => '1',
            'situacao' => 'A',
            'numeroDocumento' => '98765432100', // CPF
            'tipo' => 'F',
            'indicadorIe' => 9, // Não contribuinte
            'dataNascimento' => '1990-01-01',
            'email' => 'sanozukez@gmail.com',
            'celular' => '11999999999',
        ];

        $this->info('Payload PF:');
        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $response = Http::withToken($token)
            ->timeout(30)
            ->put("https://www.bling.com.br/Api/v3/contatos/{$contactId}", $payload);

        $this->info('Status: ' . $response->status());
        $this->line('Response:');
        $this->line(json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function updateContactPJ($token, $contactId)
    {
        if (!$contactId) {
            $this->error('ID do contato é obrigatório');
            return;
        }

        $payload = [
            'nome' => 'Aureo Teste LTDA',
            'codigo' => '1',
            'situacao' => 'A',
            'numeroDocumento' => '07228424000157', // CNPJ
            'tipo' => 'J',
            'indicadorIe' => 1, // Contribuinte ICMS
            'ie' => '535371914110',
            'fantasia' => 'Empresa Teste',
            'email' => 'sanozukez@gmail.com',
            'celular' => '11999999999',
            'contribuinte' => 1,
            'endereco' => [
                'geral' => [
                    'endereco' => 'Rua Teste',
                    'numero' => '123',
                    'bairro' => 'Centro',
                    'cep' => '01310100',
                    'municipio' => 'São Paulo',
                    'uf' => 'SP',
                    'pais' => 'Brasil'
                ]
            ]
        ];

        $this->info('Payload PJ:');
        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $response = Http::withToken($token)
            ->timeout(30)
            ->put("https://www.bling.com.br/Api/v3/contatos/{$contactId}", $payload);

        $this->info('Status: ' . $response->status());
        $this->line('Response:');
        $this->line(json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function listContacts($token)
    {
        $response = Http::withToken($token)
            ->get('https://www.bling.com.br/Api/v3/contatos', [
                'limite' => 10,
                'pagina' => 1
            ]);

        $this->info('Status: ' . $response->status());
        $this->line(json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
