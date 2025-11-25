<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class BlingListContactTypes extends Command
{
    protected $signature = 'bling:list-contact-types';
    protected $description = 'Lista todos os tipos de contato disponíveis no Bling';

    public function handle()
    {
        $this->info('🔄 Consultando tipos de contato no Bling...');
        $this->newLine();

        $token = Cache::get('bling_access_token');

        if (!$token) {
            $this->error('❌ Token de acesso não encontrado!');
            $this->warn('Execute a autenticação OAuth primeiro: http://localhost:8000/bling');
            return 1;
        }

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->get(config('services.bling.base_url') . '/contatos/tipos');

            if (!$response->successful()) {
                $this->error('❌ Erro ao consultar API do Bling');
                $this->line('Status: ' . $response->status());
                $this->line('Resposta: ' . $response->body());
                return 1;
            }

            $data = $response->json();
            $tipos = $data['data'] ?? [];

            if (empty($tipos)) {
                $this->warn('⚠️  Nenhum tipo de contato encontrado');
                return 0;
            }

            $this->info('📋 Total de tipos encontrados: ' . count($tipos));
            $this->newLine();

            // Exibir em tabela
            $headers = ['ID', 'Descrição'];
            $rows = collect($tipos)->map(function($tipo) {
                return [
                    $tipo['id'],
                    $tipo['descricao']
                ];
            })->toArray();

            $this->table($headers, $rows);

            // Verificar se existe "Cliente ecommerce"
            $clienteEcommerce = collect($tipos)->firstWhere('descricao', 'Cliente ecommerce');
            
            if ($clienteEcommerce) {
                $this->newLine();
                $this->info('✓ Tipo "Cliente ecommerce" encontrado!');
                $this->line('  ID configurado no .env: ' . config('services.bling.customer_type_id'));
                $this->line('  ID no Bling: ' . $clienteEcommerce['id']);
                
                if (config('services.bling.customer_type_id') != $clienteEcommerce['id']) {
                    $this->newLine();
                    $this->warn('⚠️  ATENÇÃO: O ID configurado não corresponde ao ID no Bling!');
                    $this->line('  Atualize o .env com: BLING_CUSTOMER_TYPE_ID=' . $clienteEcommerce['id']);
                }
            } else {
                $this->newLine();
                $this->warn('⚠️  Tipo "Cliente ecommerce" NÃO encontrado');
                $this->line('  Crie este tipo no painel do Bling em: Cadastros > Tipos de Contato');
                $this->line('  Depois execute este comando novamente para obter o ID');
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Exceção ao consultar tipos: ' . $e->getMessage());
            return 1;
        }
    }
}
