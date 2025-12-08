<?php

namespace App\Services\Backup;

use App\Models\Backup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class BackupRestoreService
{
    protected string $databaseName;
    protected string $databaseUser;
    protected string $databasePassword;
    protected string $databaseHost;
    protected int $databasePort;

    public function __construct()
    {
        $this->databaseName = config('database.connections.mysql.database');
        $this->databaseUser = config('database.connections.mysql.username');
        $this->databasePassword = config('database.connections.mysql.password');
        $this->databaseHost = config('database.connections.mysql.host');
        $this->databasePort = config('database.connections.mysql.port', 3306);
    }

    /**
     * Restaurar backup
     */
    public function restore(Backup $backup, array $options = []): bool
    {
        // Validações básicas (validação completa já foi feita no controller)
        if ($backup->status !== 'completed') {
            throw new Exception('Backup não está completo. Status: ' . $backup->status);
        }
        
        $fullPath = $backup->getFullPath();
        if (!file_exists($fullPath) || !is_readable($fullPath)) {
            throw new Exception('Arquivo de backup não encontrado ou não legível: ' . $backup->path);
        }

        $dropDatabase = $options['drop_database'] ?? false;
        $createDatabase = $options['create_database'] ?? true;

        try {
            $isCompressed = str_ends_with($backup->filename, '.gz');

            // Se for compactado, descompactar temporariamente
            $tempPath = null;
            if ($isCompressed) {
                $tempPath = storage_path('app/temp_restore_' . time() . '_' . uniqid() . '.sql');
                $this->decompressFile($fullPath, $tempPath);
                
                // Verificar se descompactação funcionou
                if (!file_exists($tempPath) || filesize($tempPath) === 0) {
                    throw new Exception('Erro ao descompactar arquivo de backup');
                }
                
                $fullPath = $tempPath;
            }

            // IMPORTANTE: NUNCA dropar o banco completo pois:
            // 1. Quebra a sessão do Laravel (se usar database driver)
            // 2. Remove usuários administradores
            // 3. Remove outras configurações importantes
            // 
            // Por isso, sempre apenas limpamos as tabelas antes de restaurar
            // Isso preserva a estrutura do banco e não quebra a sessão
            
            if ($dropDatabase) {
                Log::warning('DROP DATABASE foi solicitado, mas será ignorado por segurança', [
                    'database' => $this->databaseName,
                    'reason' => 'Preservar usuários admin e sessões'
                ]);
            }

            // Limpar todas as tabelas antes de restaurar (preserva estrutura e sessões)
            Log::info('Limpando tabelas antes de restaurar backup', ['database' => $this->databaseName]);
            $this->truncateAllTables();

            // Restaurar backup
            $this->importBackup($fullPath);

            // Limpar arquivo temporário se foi descompactado
            if ($tempPath !== null && file_exists($tempPath)) {
                @unlink($tempPath);
            }

            Log::info('Backup restaurado com sucesso', [
                'backup_id' => $backup->id,
                'filename' => $backup->filename,
            ]);

            return true;

        } catch (Exception $e) {
            // Limpar arquivo temporário em caso de erro
            if (isset($tempPath) && $tempPath !== null && file_exists($tempPath)) {
                @unlink($tempPath);
            }
            
            Log::error('Erro ao restaurar backup', [
                'backup_id' => $backup->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Descompactar arquivo .gz
     */
    protected function decompressFile(string $source, string $destination): void
    {
        $sourceHandle = gzopen($source, 'rb');
        if (!$sourceHandle) {
            throw new Exception('Não foi possível abrir o arquivo compactado');
        }

        $destHandle = fopen($destination, 'wb');
        if (!$destHandle) {
            gzclose($sourceHandle);
            throw new Exception('Não foi possível criar arquivo temporário');
        }

        while (!gzeof($sourceHandle)) {
            $data = gzread($sourceHandle, 8192);
            if ($data === false) {
                break;
            }
            fwrite($destHandle, $data);
        }

        gzclose($sourceHandle);
        fclose($destHandle);
    }

    /**
     * Dropar banco de dados
     * IMPORTANTE: Isso desconecta do banco atual, então deve ser feito com cuidado
     */
    protected function dropDatabase(): void
    {
        try {
            // Usar conexão direta sem especificar database
            $connection = DB::connection('mysql');
            $pdo = $connection->getPdo();
            
            // Executar DROP DATABASE
            // IMPORTANTE: Isso desconecta do banco atual
            $pdo->exec("DROP DATABASE IF EXISTS `{$this->databaseName}`");
            
            Log::info('Banco de dados dropado com sucesso', ['database' => $this->databaseName]);
            
            // Aguardar um pouco para garantir que o DROP foi processado
            usleep(500000); // 0.5 segundos
            
            // Reconectar (importante para não quebrar a sessão)
            // Fechar conexão atual primeiro
            $pdo = null;
            DB::purge('mysql');
            
            // Reconectar sem especificar database (conecta ao servidor MySQL)
            config(['database.connections.mysql.database' => null]);
            DB::reconnect('mysql');
            
        } catch (Exception $e) {
            // Ignorar se banco não existir, mas logar outros erros
            $errorMsg = $e->getMessage();
            if (str_contains($errorMsg, "doesn't exist") || 
                str_contains($errorMsg, "Unknown database") ||
                str_contains($errorMsg, "database doesn't exist")) {
                Log::info('Banco não existe, ignorando drop', ['database' => $this->databaseName]);
            } else {
                Log::warning('Erro ao dropar banco', [
                    'database' => $this->databaseName,
                    'error' => $errorMsg
                ]);
                // Não lançar exceção aqui, apenas logar - o banco pode não existir
            }
        }
    }

    /**
     * Limpar todas as tabelas (mais seguro que dropar banco)
     */
    protected function truncateAllTables(): void
    {
        try {
            // Desabilitar foreign key checks temporariamente
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            
            // Obter todas as tabelas
            $tables = DB::select('SHOW TABLES');
            $databaseName = DB::getDatabaseName();
            $key = 'Tables_in_' . $databaseName;
            
            foreach ($tables as $table) {
                $tableName = $table->$key;
                // Pular tabela de sessões para não quebrar a sessão atual
                if ($tableName === 'sessions') {
                    Log::info('Pulando tabela de sessões', ['table' => $tableName]);
                    continue;
                }
                DB::statement("TRUNCATE TABLE `{$tableName}`");
                Log::debug('Tabela truncada', ['table' => $tableName]);
            }
            
            // Reabilitar foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            
            Log::info('Todas as tabelas foram limpas', ['database' => $this->databaseName]);
            
        } catch (Exception $e) {
            // Reabilitar foreign key checks em caso de erro
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            } catch (Exception $e2) {
                // Ignorar
            }
            
            Log::error('Erro ao limpar tabelas', [
                'database' => $this->databaseName,
                'error' => $e->getMessage()
            ]);
            throw new Exception('Erro ao limpar tabelas: ' . $e->getMessage());
        }
    }

    /**
     * Criar banco de dados
     */
    protected function createDatabase(): void
    {
        try {
            // Usar conexão direta para criar banco
            $connection = DB::connection('mysql');
            $pdo = $connection->getPdo();
            
            // Criar banco se não existir
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$this->databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            Log::info('Banco de dados criado/verificado com sucesso', ['database' => $this->databaseName]);
            
            // Reconectar ao banco criado (importante para garantir conexão)
            $pdo = null;
            DB::purge('mysql');
            
            // Restaurar configuração do database
            config(['database.connections.mysql.database' => $this->databaseName]);
            DB::reconnect('mysql');
            
        } catch (Exception $e) {
            Log::error('Erro ao criar banco de dados', [
                'database' => $this->databaseName,
                'error' => $e->getMessage()
            ]);
            throw new Exception('Erro ao criar banco de dados: ' . $e->getMessage());
        }
    }

    /**
     * Importar backup SQL
     */
    protected function importBackup(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw new Exception('Arquivo de backup não encontrado: ' . $filePath);
        }

        if (!is_readable($filePath)) {
            throw new Exception('Arquivo de backup não é legível: ' . $filePath);
        }

        // Preparar variáveis de ambiente
        $env = $_ENV;
        $env['MYSQL_PWD'] = $this->databasePassword;
        
        // Ler conteúdo do arquivo SQL
        $sqlContent = file_get_contents($filePath);
        
        if ($sqlContent === false || empty($sqlContent)) {
            throw new Exception('Arquivo de backup está vazio ou não pode ser lido');
        }

        // Construir comando mysql
        $command = [
            'mysql',
            '-h', $this->databaseHost,
            '-P', (string)$this->databasePort,
            '-u', $this->databaseUser,
            $this->databaseName,
        ];

        // Executar comando
        $descriptorspec = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w'],  // stderr
        ];
        
        $process = proc_open($command, $descriptorspec, $pipes, null, $env);
        
        if (!is_resource($process)) {
            throw new Exception('Não foi possível iniciar o processo mysql');
        }
        
        // Escrever SQL no stdin
        fwrite($pipes[0], $sqlContent);
        fclose($pipes[0]);
        
        // Ler stdout (geralmente vazio em importação)
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        
        // Ler stderr (erros)
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        
        $returnCode = proc_close($process);
        
        if ($returnCode !== 0) {
            Log::error('BackupRestoreService: Erro ao importar backup', [
                'command' => implode(' ', $command),
                'error' => $error,
                'output' => $output,
                'return_code' => $returnCode,
                'file_size' => filesize($filePath),
            ]);
            throw new Exception('Erro ao importar backup (código ' . $returnCode . '): ' . $error);
        }
    }

    /**
     * Validar backup antes de restaurar
     */
    public function validateBackup(Backup $backup): array
    {
        $errors = [];
        $warnings = [];

        if ($backup->status !== 'completed') {
            $errors[] = 'Backup não está completo. Status: ' . $backup->status;
        }

        // Verificar existência do arquivo usando file_exists diretamente
        $fullPath = $backup->getFullPath();
        $fileExists = file_exists($fullPath) && is_readable($fullPath);
        
        if (!$fileExists) {
            $errors[] = 'Arquivo de backup não encontrado ou não legível: ' . $backup->path;
        }

        // Verificar tamanho do arquivo
        if ($fileExists) {
            $actualSize = filesize($fullPath);
            
            if ($actualSize === false || $actualSize === 0) {
                $errors[] = 'Arquivo de backup está vazio ou não pode ser lido';
            } else {
                // Comparar tamanho (com tolerância de 1KB para diferenças de encoding)
                $sizeDiff = abs($actualSize - $backup->size);
                if ($sizeDiff > 1024) {
                    $warnings[] = sprintf(
                        'Tamanho do arquivo (%s) não corresponde ao registro no banco (%s). Diferença: %s bytes',
                        number_format($actualSize),
                        number_format($backup->size),
                        number_format($sizeDiff)
                    );
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
}

