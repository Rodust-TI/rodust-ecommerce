<?php

namespace App\Console\Commands;

use App\Services\ERP\BlingV3Adapter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BlingRefreshToken extends Command
{
    protected $signature = 'bling:refresh-token';
    protected $description = 'Forçar renovação do token de acesso do Bling';

    public function handle(BlingV3Adapter $bling): int
    {
        $this->info("═══════════════════════════════════════════════");
        $this->info("         RENOVAR TOKEN DO BLING");
        $this->info("═══════════════════════════════════════════════\n");

        $this->info("Tentando renovar token de acesso...\n");

        try {
            // Forçar refresh chamando um endpoint simples
            $result = $bling->testConnection();
            
            if ($result) {
                $this->info("✓ Token renovado com sucesso!");
                $this->line("  O token foi atualizado e está pronto para uso.\n");
                return Command::SUCCESS;
            } else {
                $this->error("✗ Falha ao renovar token.");
                $this->line("  Verifique os logs para mais detalhes.\n");
                return Command::FAILURE;
            }
            
        } catch (\Exception $e) {
            $this->error("✗ Erro ao renovar token: " . $e->getMessage());
            $this->newLine();
            $this->line("Possíveis causas:");
            $this->line("  - Refresh token expirado (necessário reautenticar)");
            $this->line("  - Credenciais do Bling inválidas");
            $this->line("  - Problema de conexão com a API do Bling");
            $this->newLine();
            $this->line("Para resolver:");
            $this->line("  1. Acesse o painel do Bling");
            $this->line("  2. Reconecte a integração");
            $this->line("  3. Tente novamente\n");
            
            return Command::FAILURE;
        }
    }
}
