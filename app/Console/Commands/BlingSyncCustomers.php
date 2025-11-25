<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Jobs\SyncCustomerToBling;
use Illuminate\Console\Command;

class BlingSyncCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bling:sync-customers 
                            {--limit=100 : Number of customers to sync}
                            {--only-verified : Only sync verified customers}
                            {--force : Force sync even if already synced}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync customers to Bling ERP';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Iniciando sincronização de clientes...');
        $this->newLine();

        $limit = $this->option('limit');
        $onlyVerified = $this->option('only-verified');
        $force = $this->option('force');

        // Query builder
        $query = Customer::query();

        if ($onlyVerified) {
            $query->whereNotNull('email_verified_at');
        }

        if (!$force) {
            $query->whereNull('bling_id');
        }

        $customers = $query->limit($limit)->get();

        if ($customers->isEmpty()) {
            $this->warn('Nenhum cliente encontrado para sincronizar.');
            return 0;
        }

        $this->info("📥 {$customers->count()} clientes serão sincronizados");
        $this->newLine();

        $bar = $this->output->createProgressBar($customers->count());
        $bar->start();

        $stats = [
            'dispatched' => 0,
            'skipped' => 0,
        ];

        foreach ($customers as $customer) {
            if (!$onlyVerified || $customer->email_verified_at) {
                SyncCustomerToBling::dispatch($customer);
                $stats['dispatched']++;
            } else {
                $stats['skipped']++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('📊 Estatísticas da Sincronização:');
        $this->newLine();
        $this->line("  ✅ Enfileirados: {$stats['dispatched']}");
        $this->line("  ⏭️  Ignorados: {$stats['skipped']}");
        $this->newLine();

        $this->info('✅ Jobs enfileirados! Execute o queue worker para processar.');
        $this->comment('   php artisan queue:work');

        return 0;
    }
}
