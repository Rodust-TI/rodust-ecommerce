<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Backup extends Model
{
    protected $fillable = [
        'filename',
        'path',
        'size',
        'type',
        'status',
        'cloud_provider',
        'cloud_path',
        'database_name',
        'mysql_version',
        'started_at',
        'completed_at',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'size' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Formatar tamanho do arquivo
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Verificar se arquivo existe
     */
    public function fileExists(): bool
    {
        // Verificar tanto via Storage quanto via file_exists para garantir
        $storageExists = Storage::disk('local')->exists($this->path);
        $fileExists = file_exists($this->getFullPath());
        
        return $storageExists || $fileExists;
    }

    /**
     * Obter caminho completo do arquivo
     */
    public function getFullPath(): string
    {
        return storage_path('app/' . $this->path);
    }
}
