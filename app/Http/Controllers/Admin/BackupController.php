<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Services\Backup\BackupService;
use App\Services\Backup\BackupRestoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    protected BackupService $backupService;
    protected BackupRestoreService $restoreService;

    public function __construct(BackupService $backupService, BackupRestoreService $restoreService)
    {
        $this->backupService = $backupService;
        $this->restoreService = $restoreService;
    }

    /**
     * Dashboard de backups
     * GET /admin/backups
     */
    public function index()
    {
        $stats = $this->backupService->getStats();
        $recentBackups = Backup::orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.backups.index', compact('stats', 'recentBackups'));
    }

    /**
     * Criar backup manual
     * POST /admin/backups/create
     */
    public function create(Request $request)
    {
        $request->validate([
            'compress' => 'boolean',
        ]);

        try {
            $backup = $this->backupService->createBackup([
                'compress' => $request->boolean('compress', false),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Backup criado com sucesso!',
                'backup' => $backup,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar backup: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Histórico de backups
     * GET /admin/backups/history
     */
    public function history(Request $request)
    {
        $backups = $this->backupService->listBackups(20);

        return view('admin.backups.history', compact('backups'));
    }

    /**
     * Download de backup
     * GET /admin/backups/{backup}/download
     */
    public function download(Backup $backup)
    {
        if (!$backup->fileExists()) {
            return redirect()->route('admin.backups.history')
                ->with('error', 'Arquivo de backup não encontrado.');
        }

        $fullPath = $backup->getFullPath();
        
        if (!file_exists($fullPath)) {
            return redirect()->route('admin.backups.history')
                ->with('error', 'Arquivo de backup não encontrado no sistema de arquivos.');
        }

        // Determinar Content-Type baseado na extensão
        $contentType = str_ends_with($backup->filename, '.gz') 
            ? 'application/gzip' 
            : 'application/sql';
        
        // Usar download direto para melhor performance
        return response()->download($fullPath, $backup->filename, [
            'Content-Type' => $contentType,
        ]);
    }

    /**
     * Deletar backup
     * DELETE /admin/backups/{backup}
     */
    public function destroy(Backup $backup)
    {
        try {
            $this->backupService->deleteBackup($backup);

            return response()->json([
                'success' => true,
                'message' => 'Backup deletado com sucesso!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao deletar backup: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restaurar backup
     * POST /admin/backups/{backup}/restore
     */
    public function restore(Request $request, Backup $backup)
    {
        try {
            $request->validate([
                'drop_database' => 'boolean',
                'create_database' => 'boolean',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos: ' . $e->getMessage(),
            ], 422);
        }

        try {
            // Validar backup antes de restaurar
            $validation = $this->restoreService->validateBackup($backup);
            
            if (!$validation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup inválido: ' . implode(', ', $validation['errors']),
                    'errors' => $validation['errors'],
                    'warnings' => $validation['warnings'],
                ], 400);
            }

            if (!empty($validation['warnings'])) {
                // Avisar sobre warnings, mas permitir continuar
                Log::warning('BackupRestore: Warnings encontrados', [
                    'backup_id' => $backup->id,
                    'warnings' => $validation['warnings'],
                ]);
            }

            // Executar restauração
            // IMPORTANTE: Se drop_database for true, a sessão pode ser perdida temporariamente
            // Por isso, salvamos a sessão antes e não dependemos dela durante a restauração
            $dropDb = $request->boolean('drop_database', false);
            $createDb = $request->boolean('create_database', true);
            
            Log::info('Iniciando restauração de backup', [
                'backup_id' => $backup->id,
                'drop_database' => $dropDb,
                'create_database' => $createDb,
            ]);
            
            // Salvar sessão antes de dropar banco (se necessário)
            if ($dropDb) {
                session()->save();
            }
            
            $this->restoreService->restore($backup, [
                'drop_database' => $dropDb,
                'create_database' => $createDb,
            ]);

            Log::info('Restauração de backup concluída com sucesso', [
                'backup_id' => $backup->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Backup restaurado com sucesso!',
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao restaurar backup', [
                'backup_id' => $backup->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao restaurar backup: ' . $e->getMessage(),
                'error_type' => get_class($e),
            ], 500);
        } catch (\Throwable $e) {
            // Capturar qualquer erro fatal também
            Log::error('Erro fatal ao restaurar backup', [
                'backup_id' => $backup->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro fatal ao restaurar backup: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validar backup
     * GET /admin/backups/{backup}/validate
     */
    public function validate(Backup $backup)
    {
        $validation = $this->restoreService->validateBackup($backup);

        return response()->json([
            'success' => $validation['valid'],
            'valid' => $validation['valid'],
            'errors' => $validation['errors'],
            'warnings' => $validation['warnings'],
        ]);
    }

    /**
     * Executar refresh do banco (re-executar migrations)
     * POST /admin/backups/refresh-database
     */
    public function refreshDatabase(Request $request)
    {
        $request->validate([
            'seed' => 'boolean',
        ]);

        try {
            $seed = $request->boolean('seed', false);
            
            Log::info('Iniciando refresh do banco de dados via painel admin', [
                'seed' => $seed,
            ]);

            // Executar comando Artisan
            \Illuminate\Support\Facades\Artisan::call('db:refresh', [
                '--force' => true,
                '--seed' => $seed,
            ]);

            $output = \Illuminate\Support\Facades\Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Banco de dados atualizado com sucesso!',
                'output' => $output,
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao executar refresh do banco', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar banco de dados: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Configurações de backup
     * GET /admin/backups/settings
     */
    public function settings()
    {
        return view('admin.backups.settings');
    }
}

