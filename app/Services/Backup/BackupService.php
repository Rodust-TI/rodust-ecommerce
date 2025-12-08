<?php

namespace App\Services\Backup;

use App\Models\Backup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class BackupService
{
    protected string $backupPath;
    protected string $databaseName;
    protected string $databaseUser;
    protected string $databasePassword;
    protected string $databaseHost;
    protected int $databasePort;

    public function __construct()
    {
        $this->backupPath = 'backups';
        $this->databaseName = config('database.connections.mysql.database');
        $this->databaseUser = config('database.connections.mysql.username');
        $this->databasePassword = config('database.connections.mysql.password');
        $this->databaseHost = config('database.connections.mysql.host');
        $this->databasePort = config('database.connections.mysql.port', 3306);
    }

    /**
     * Criar backup do banco de dados
     */
    public function createBackup(array $options = []): Backup
    {
        $compress = $options['compress'] ?? false;
        $filename = $this->generateFilename($compress);
        $path = $this->backupPath . '/' . $filename;

        // Criar registro no banco
        $backup = Backup::create([
            'filename' => $filename,
            'path' => $path,
            'size' => 0,
            'type' => 'local',
            'status' => 'running',
            'database_name' => $this->databaseName,
            'mysql_version' => $this->getMysqlVersion(),
            'started_at' => now(),
            'metadata' => [
                'compressed' => $compress,
                'options' => $options,
            ],
        ]);

        try {
            // Criar diretório se não existir com permissões corretas
            // Em Docker no Windows, volumes montados precisam de 0777 para funcionar
            // Em produção, o sistema criará com permissões adequadas
            $backupDir = storage_path('app/' . $this->backupPath);
            if (!is_dir($backupDir)) {
                if (!@mkdir($backupDir, 0777, true) && !is_dir($backupDir)) {
                    throw new Exception('Não foi possível criar o diretório de backups: ' . $backupDir);
                }
                // Garantir permissões de escrita (0777 para volumes Docker do Windows)
                @chmod($backupDir, 0777);
            } else {
                // Garantir que o diretório existente tem permissões corretas
                if (!is_writable($backupDir)) {
                    @chmod($backupDir, 0777);
                }
            }

            // Executar mysqldump
            $fullPath = storage_path('app/' . $path);
            $this->executeMysqldump($fullPath, $compress);
            
            // Verificar se arquivo foi criado
            if (!file_exists($fullPath)) {
                throw new Exception('Arquivo de backup não foi criado em: ' . $fullPath);
            }
            
            // Verificar tamanho
            $fileSize = filesize($fullPath);
            if ($fileSize === 0) {
                throw new Exception('Arquivo de backup está vazio');
            }
            $backup->update([
                'size' => $fileSize,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            return $backup;

        } catch (Exception $e) {
            $backup->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * Gerar nome do arquivo
     */
    protected function generateFilename(bool $compress): string
    {
        $extension = $compress ? 'sql.gz' : 'sql';
        $timestamp = now()->format('Y-m-d_His');
        return "backup_{$this->databaseName}_{$timestamp}.{$extension}";
    }

    /**
     * Executar mysqldump e salvar output
     */
    protected function executeMysqldump(string $outputFile, bool $compress): void
    {
        // Preparar variáveis de ambiente
        $env = $_ENV;
        $env['MYSQL_PWD'] = $this->databasePassword;
        
        // Construir comando
        $command = [
            'mysqldump',
            '-h', $this->databaseHost,
            '-P', (string)$this->databasePort,
            '-u', $this->databaseUser,
            $this->databaseName,
            '--single-transaction',
            '--routines',
            '--triggers',
            '--events',
        ];
        
        // Executar comando
        $descriptorspec = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w'],  // stderr
        ];
        
        $process = proc_open($command, $descriptorspec, $pipes, null, $env);
        
        if (!is_resource($process)) {
            throw new Exception('Não foi possível iniciar o processo mysqldump');
        }
        
        // Fechar stdin
        fclose($pipes[0]);
        
        // Ler stdout
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        
        // Ler stderr
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        
        $returnCode = proc_close($process);
        
        if ($returnCode !== 0) {
            throw new Exception('Erro ao executar mysqldump (código ' . $returnCode . '): ' . $error);
        }
        
        if (empty($output)) {
            throw new Exception('mysqldump não retornou dados. Erro: ' . $error);
        }
        
        // Compactar se necessário
        if ($compress) {
            $output = gzencode($output, 9);
        }
        
        // Garantir que o diretório existe e tem permissões
        $outputDir = dirname($outputFile);
        if (!is_dir($outputDir)) {
            if (!mkdir($outputDir, 0777, true) && !is_dir($outputDir)) {
                throw new Exception('Não foi possível criar o diretório: ' . $outputDir);
            }
        }
        
        // Garantir permissões de escrita (0777 para funcionar em volumes Docker do Windows)
        // Em produção, isso será ajustado pelo sistema
        @chmod($outputDir, 0777);
        
        // Salvar arquivo
        $bytesWritten = @file_put_contents($outputFile, $output);
        
        if ($bytesWritten === false) {
            // Tentar criar o diretório novamente e ajustar permissões
            @mkdir($outputDir, 0777, true);
            @chmod($outputDir, 0777);
            
            $bytesWritten = @file_put_contents($outputFile, $output);
            
            if ($bytesWritten === false) {
                $error = error_get_last();
                throw new Exception('Não foi possível escrever o arquivo de backup: ' . $outputFile . '. Erro: ' . ($error['message'] ?? 'Desconhecido'));
            }
        }
        
        // Garantir permissões de leitura no arquivo criado (0666 para volumes Docker)
        @chmod($outputFile, 0666);
        
        if ($bytesWritten === 0) {
            throw new Exception('Arquivo de backup está vazio');
        }
    }

    /**
     * Obter versão do MySQL
     */
    protected function getMysqlVersion(): string
    {
        try {
            $version = DB::selectOne('SELECT VERSION() as version');
            return $version->version ?? 'unknown';
        } catch (Exception $e) {
            return 'unknown';
        }
    }

    /**
     * Listar backups
     */
    public function listBackups(int $limit = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Backup::orderBy('created_at', 'desc')
            ->paginate($limit);
    }

    /**
     * Deletar backup
     */
    public function deleteBackup(Backup $backup): bool
    {
        try {
            // Deletar arquivo local
            if (Storage::disk('local')->exists($backup->path)) {
                Storage::disk('local')->delete($backup->path);
            }

            // Deletar registro
            $backup->delete();

            return true;
        } catch (Exception $e) {
            throw new Exception('Erro ao deletar backup: ' . $e->getMessage());
        }
    }

    /**
     * Obter estatísticas
     */
    public function getStats(): array
    {
        $lastBackup = Backup::where('status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->first();

        $totalSize = Backup::where('status', 'completed')
            ->sum('size');

        $backupCount = Backup::where('status', 'completed')->count();

        // Calcular espaço em disco
        // Garantir que o diretório existe
        $backupDir = storage_path('app/' . $this->backupPath);
        if (!is_dir($backupDir)) {
            Storage::disk('local')->makeDirectory($this->backupPath);
        }
        
        // Usar o diretório storage como base (mais confiável)
        $storagePath = storage_path();
        $diskFree = disk_free_space($storagePath);
        $diskTotal = disk_total_space($storagePath);
        $diskUsed = $diskTotal - $diskFree;

        return [
            'last_backup' => $lastBackup?->completed_at,
            'total_backups' => $backupCount,
            'total_size' => $totalSize,
            'disk_free' => $diskFree,
            'disk_total' => $diskTotal,
            'disk_used' => $diskUsed,
            'disk_used_percent' => $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 2) : 0,
        ];
    }
}

