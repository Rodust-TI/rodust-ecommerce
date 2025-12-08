<?php

namespace App\Services\Storage;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * Service: Armazenamento de PDFs de Nota Fiscal
 * 
 * Abstrai o armazenamento para permitir trocar facilmente entre:
 * - Local (simulação)
 * - DigitalOcean Spaces
 * - Cloudflare R2
 */
class InvoiceStorageService
{
    protected string $disk;

    public function __construct()
    {
        // Por padrão, usar disk 'invoices' (será configurado em config/filesystems.php)
        $this->disk = config('filesystems.invoice_disk', 'invoices');
    }

    /**
     * Armazenar PDF da NF
     * 
     * @param string $invoiceKey Chave de acesso da NF
     * @param string $pdfContent Conteúdo binário do PDF
     * @param string $type Tipo (nfe ou nfce)
     * @return string URL pública do PDF
     */
    public function store(string $invoiceKey, string $pdfContent, string $type = 'nfe'): string
    {
        try {
            // Criar path: invoices/{ano}/{mes}/{chave}.pdf
            $year = date('Y');
            $month = date('m');
            $filename = "{$invoiceKey}.pdf";
            $path = "{$type}/{$year}/{$month}/{$filename}";

            // Salvar arquivo
            Storage::disk($this->disk)->put($path, $pdfContent);

            // Retornar URL pública
            $url = Storage::disk($this->disk)->url($path);

            Log::info('Invoice PDF stored', [
                'invoice_key' => $invoiceKey,
                'path' => $path,
                'url' => $url,
            ]);

            return $url;

        } catch (\Exception $e) {
            Log::error('Error storing invoice PDF', [
                'invoice_key' => $invoiceKey,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Obter URL pública do PDF
     */
    public function getUrl(string $invoiceKey, string $type = 'nfe'): ?string
    {
        try {
            // Tentar encontrar o arquivo (pode estar em qualquer mês/ano)
            $year = date('Y');
            $month = date('m');
            $filename = "{$invoiceKey}.pdf";
            $path = "{$type}/{$year}/{$month}/{$filename}";

            if (Storage::disk($this->disk)->exists($path)) {
                return Storage::disk($this->disk)->url($path);
            }

            // Se não encontrou no mês atual, buscar em outros meses (busca simples)
            // Em produção, pode melhorar isso com um índice no banco
            return null;

        } catch (\Exception $e) {
            Log::error('Error getting invoice PDF URL', [
                'invoice_key' => $invoiceKey,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Deletar PDF
     */
    public function delete(string $invoiceKey, string $type = 'nfe'): bool
    {
        try {
            $year = date('Y');
            $month = date('m');
            $filename = "{$invoiceKey}.pdf";
            $path = "{$type}/{$year}/{$month}/{$filename}";

            return Storage::disk($this->disk)->delete($path);

        } catch (\Exception $e) {
            Log::error('Error deleting invoice PDF', [
                'invoice_key' => $invoiceKey,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

