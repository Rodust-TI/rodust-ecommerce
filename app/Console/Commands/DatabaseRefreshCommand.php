<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseRefreshCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:refresh 
                            {--force : Forçar execução sem confirmação}
                            {--seed : Executar seeders após refresh}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-executar todas as migrations (equivalente a dropar e recriar tabelas, mas preserva estrutura do banco)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force')) {
            if (!$this->confirm('⚠️ ATENÇÃO: Isso irá dropar e recriar TODAS as tabelas do banco de dados. Todos os dados serão perdidos. Continuar?')) {
                $this->info('Operação cancelada.');
                return 0;
            }
        }

        $this->info('🔄 Iniciando refresh do banco de dados...');

        try {
            // Executar migrate:fresh (dropa todas as tabelas e recria)
            $this->info('📋 Executando migrations...');
            
            $command = 'migrate:fresh';
            if ($this->option('seed')) {
                $command .= ' --seed';
            }
            
            Artisan::call($command, [], $this->getOutput());
            
            $this->info('✅ Banco de dados atualizado com sucesso!');
            
            if ($this->option('seed')) {
                $this->info('✅ Seeders executados com sucesso!');
            }
            
            Log::info('Database refresh executado com sucesso', [
                'seeded' => $this->option('seed'),
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Erro ao atualizar banco de dados: ' . $e->getMessage());
            
            Log::error('Erro ao executar database refresh', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }
}
