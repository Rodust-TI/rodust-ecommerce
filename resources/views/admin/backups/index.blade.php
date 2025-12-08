@extends('admin.layout')

@section('title', 'Backups')
@section('page-title', 'Backups')
@section('page-description', 'Gerenciar backups do banco de dados')

@section('content')
<div class="space-y-6">
    <!-- Status Card -->
    <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-white mb-4">Status dos Backups</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gray-700 rounded-lg p-4">
                <div class="text-sm text-gray-400 mb-1">Último Backup</div>
                <div class="text-xl font-bold text-white">
                    @if($stats['last_backup'])
                        {{ $stats['last_backup']->format('d/m/Y H:i') }}
                    @else
                        --
                    @endif
                </div>
                <div class="text-xs text-gray-500 mt-1">
                    @if($stats['last_backup'])
                        {{ $stats['last_backup']->diffForHumans() }}
                    @else
                        Ainda não realizado
                    @endif
                </div>
            </div>
            <div class="bg-gray-700 rounded-lg p-4">
                <div class="text-sm text-gray-400 mb-1">Total de Backups</div>
                <div class="text-xl font-bold text-white">{{ $stats['total_backups'] }}</div>
                <div class="text-xs text-gray-500 mt-1">
                    {{ number_format($stats['total_size'] / 1024 / 1024, 2) }} MB total
                </div>
            </div>
            <div class="bg-gray-700 rounded-lg p-4">
                <div class="text-sm text-gray-400 mb-1">Espaço em Disco</div>
                <div class="text-xl font-bold text-white">
                    {{ number_format($stats['disk_used_percent'], 1) }}%
                </div>
                <div class="text-xs text-gray-500 mt-1">
                    {{ number_format($stats['disk_free'] / 1024 / 1024 / 1024, 2) }} GB livre
                </div>
            </div>
        </div>
    </div>

    <!-- Ações -->
    <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-white mb-4">Ações</h3>
        <div class="flex flex-wrap gap-4">
            <button id="create-backup-btn" 
                    class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                🔄 Criar Backup Agora
            </button>
            <button id="create-backup-compressed-btn" 
                    class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors">
                📦 Criar Backup Compactado
            </button>
            <a href="{{ route('admin.backups.settings') }}" 
               class="px-6 py-3 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors">
                ⚙️ Configurações
            </a>
            <a href="{{ route('admin.backups.history') }}" 
               class="px-6 py-3 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors">
                📋 Histórico
            </a>
        </div>
        <div id="backup-messages" class="mt-4 hidden"></div>
    </div>

    <!-- Backups Recentes -->
    @if($recentBackups->count() > 0)
    <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-white mb-4">Backups Recentes</h3>
        <div class="space-y-2">
            @foreach($recentBackups as $backup)
            <div class="bg-gray-700 rounded-lg p-4 flex items-center justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-3">
                        <span class="text-xl">
                            @if($backup->status === 'completed') ✅
                            @elseif($backup->status === 'failed') ❌
                            @elseif($backup->status === 'running') ⏳
                            @else ⏸️
                            @endif
                        </span>
                        <div>
                            <div class="font-medium text-white">{{ $backup->filename }}</div>
                            <div class="text-sm text-gray-400">
                                {{ $backup->created_at->format('d/m/Y H:i:s') }} • 
                                {{ $backup->formatted_size }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex gap-2">
                    @if($backup->status === 'completed')
                        @php
                            $fullPath = $backup->getFullPath();
                            $fileExists = file_exists($fullPath) && is_readable($fullPath);
                        @endphp
                        @if($fileExists)
                        <a href="{{ route('admin.backups.download', $backup) }}" 
                           class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm transition-colors">
                            📥 Download
                        </a>
                        <button onclick="restoreBackup({{ $backup->id }})" 
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm transition-colors">
                            🔄 Restaurar
                        </button>
                        @else
                        <span class="px-4 py-2 bg-yellow-600 text-white rounded-lg text-sm" title="Path: {{ $backup->path }}">
                            ⚠️ Arquivo não encontrado
                        </span>
                        @endif
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const createBtn = document.getElementById('create-backup-btn');
    const createCompressedBtn = document.getElementById('create-backup-compressed-btn');
    const messagesDiv = document.getElementById('backup-messages');

    function showMessage(type, message) {
        const bgColor = type === 'success' ? 'bg-green-900 border-green-700 text-green-200' : 
                       type === 'error' ? 'bg-red-900 border-red-700 text-red-200' : 
                       'bg-blue-900 border-blue-700 text-blue-200';
        
        messagesDiv.className = bgColor + ' border-l-4 p-4 rounded-lg';
        messagesDiv.innerHTML = message;
        messagesDiv.classList.remove('hidden');
    }

    function createBackup(compress = false) {
        const btn = compress ? createCompressedBtn : createBtn;
        const originalText = btn.textContent;
        
        btn.disabled = true;
        btn.textContent = compress ? '📦 Criando backup compactado...' : '🔄 Criando backup...';
        messagesDiv.classList.add('hidden');

        fetch('{{ route("admin.backups.create") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ compress: compress })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('success', '✅ Backup criado com sucesso! Recarregando página...');
                setTimeout(() => window.location.reload(), 2000);
            } else {
                showMessage('error', '❌ Erro: ' + data.message);
                btn.disabled = false;
                btn.textContent = originalText;
            }
        })
        .catch(error => {
            showMessage('error', '❌ Erro ao criar backup: ' + error.message);
            btn.disabled = false;
            btn.textContent = originalText;
        });
    }

    createBtn.addEventListener('click', () => createBackup(false));
    createCompressedBtn.addEventListener('click', () => createBackup(true));
});

function restoreBackup(id) {
    if (!confirm('⚠️ ATENÇÃO: Restaurar este backup irá:\n\n' +
                 '• Substituir TODOS os dados atuais do banco\n' +
                 '• Esta ação NÃO pode ser desfeita\n\n' +
                 'Deseja continuar?\n\n' +
                 'Recomendamos criar um backup antes de restaurar.')) {
        return;
    }

    const dropDb = confirm('Deseja dropar o banco antes de restaurar?\n\n' +
                          'SIM: Remove o banco e cria novamente (mais seguro)\n' +
                          'NÃO: Tenta restaurar sobre o banco existente');

    if (!confirm('🔴 ÚLTIMA CONFIRMAÇÃO\n\n' +
                 'Você tem CERTEZA que deseja restaurar este backup?\n\n' +
                 'Todos os dados atuais serão PERDIDOS!')) {
        return;
    }

    fetch(`/admin/backups/${id}/restore`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            drop_database: dropDb,
            create_database: true
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Backup restaurado com sucesso!\n\nA página será recarregada.');
            window.location.reload();
        } else {
            alert('❌ Erro ao restaurar: ' + data.message);
        }
    })
    .catch(error => {
        alert('❌ Erro ao restaurar backup: ' + error.message);
    });
}
</script>
@endpush
@endsection

