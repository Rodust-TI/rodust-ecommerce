<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

class CheckQueueWorker extends Command
{
    protected $signature = 'queue:check';
    protected $description = 'Verificar se o queue worker está rodando e processando jobs';

    public function handle()
    {
        $this->info('🔍 Verificando status do Queue Worker...');
        $this->newLine();

        // Verificar conexão Redis
        try {
            Redis::ping();
            $this->info('✅ Redis conectado');
        } catch (\Exception $e) {
            $this->error('❌ Redis não conectado: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // Verificar jobs pendentes
        $pendingJobs = Queue::size('default');
        $this->info("📊 Jobs pendentes na fila: {$pendingJobs}");

        // Verificar jobs falhados
        try {
            $failedJobs = \DB::table('failed_jobs')->count();
            if ($failedJobs > 0) {
                $this->warn("⚠️  Jobs falhados: {$failedJobs}");
                $this->line("   Execute: php artisan queue:failed para ver detalhes");
            } else {
                $this->info('✅ Nenhum job falhado');
            }
        } catch (\Exception $e) {
            $this->warn('⚠️  Não foi possível verificar jobs falhados');
        }

        $this->newLine();
        $this->info('💡 Para iniciar o queue worker:');
        $this->line('   docker exec docker-laravel.queue-1 php artisan queue:work redis --tries=3');
        $this->newLine();
        $this->info('💡 Para verificar se o container está rodando:');
        $this->line('   docker ps | grep queue');

        return Command::SUCCESS;
    }
}

