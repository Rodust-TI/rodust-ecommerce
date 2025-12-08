@extends('admin.layout')

@section('title', 'Histórico de Backups')
@section('page-title', 'Histórico')
@section('page-description', 'Visualizar e gerenciar backups anteriores')

@section('content')
<div class="space-y-6">
    <!-- Tabela de Backups -->
    <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
        <div class="p-6 border-b border-gray-700">
            <h3 class="text-lg font-semibold text-white">Histórico de Backups</h3>
        </div>
        
        @if($backups->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Arquivo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Tamanho</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Criado em</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @foreach($backups as $backup)
                    <tr class="hover:bg-gray-700/50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-white">{{ $backup->filename }}</div>
                            <div class="text-xs text-gray-400">{{ $backup->database_name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($backup->status === 'completed')
                                <span class="px-2 py-1 text-xs font-semibold rounded bg-green-900 text-green-200">✅ Concluído</span>
                            @elseif($backup->status === 'failed')
                                <span class="px-2 py-1 text-xs font-semibold rounded bg-red-900 text-red-200">❌ Falhou</span>
                            @elseif($backup->status === 'running')
                                <span class="px-2 py-1 text-xs font-semibold rounded bg-blue-900 text-blue-200">⏳ Em execução</span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded bg-gray-700 text-gray-300">⏸️ Pendente</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                            {{ $backup->formatted_size }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                            {{ $backup->created_at->format('d/m/Y H:i:s') }}
                            <div class="text-xs text-gray-500">{{ $backup->created_at->diffForHumans() }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex gap-2 flex-wrap">
                                @if($backup->status === 'completed')
                                    @php
                                        // Verificar existência do arquivo de forma mais robusta
                                        $fullPath = $backup->getFullPath();
                                        $fileExists = file_exists($fullPath) && is_readable($fullPath);
                                    @endphp
                                    @if($fileExists)
                                    <a href="{{ route('admin.backups.download', $backup) }}" 
                                       class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs transition-colors">
                                        📥 Download
                                    </a>
                                    <button onclick="restoreBackup({{ $backup->id }})" 
                                            class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white rounded text-xs transition-colors">
                                        🔄 Restaurar
                                    </button>
                                    @else
                                    <span class="px-3 py-1 bg-yellow-600 text-white rounded text-xs" title="Path: {{ $backup->path }} | Full: {{ $fullPath }}">
                                        ⚠️ Arquivo não encontrado
                                    </span>
                                    @endif
                                @elseif($backup->status === 'failed')
                                    <span class="px-3 py-1 bg-red-600 text-white rounded text-xs" title="{{ $backup->error_message ?? 'Erro desconhecido' }}">
                                        ❌ Falhou
                                    </span>
                                @endif
                                <button onclick="deleteBackup({{ $backup->id }})" 
                                        class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-xs transition-colors">
                                    🗑️ Deletar
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="p-4 border-t border-gray-700">
            {{ $backups->links() }}
        </div>
        @else
        <div class="p-12 text-center">
            <span class="text-6xl block mb-4">📦</span>
            <p class="text-gray-400 text-lg">Nenhum backup encontrado</p>
            <p class="text-gray-500 text-sm mt-2">Crie seu primeiro backup no dashboard</p>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function deleteBackup(id) {
    if (!confirm('⚠️ ATENÇÃO: Tem certeza que deseja deletar este backup?\n\nEsta ação não pode ser desfeita.')) {
        return;
    }

    fetch(`/admin/backups/${id}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Backup deletado com sucesso!');
            window.location.reload();
        } else {
            alert('❌ Erro ao deletar: ' + data.message);
        }
    })
    .catch(error => {
        alert('❌ Erro ao deletar backup: ' + error.message);
    });
}

function restoreBackup(id) {
    if (!confirm('⚠️ ATENÇÃO: Restaurar este backup irá:\n\n' +
                 '• Limpar TODAS as tabelas (exceto sessões)\n' +
                 '• Restaurar os dados do backup\n' +
                 '• Esta ação NÃO pode ser desfeita\n\n' +
                 'Deseja continuar?\n\n' +
                 'Recomendamos criar um backup antes de restaurar.')) {
        return;
    }

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
            drop_database: false, // Nunca dropar banco (preserva usuários admin e sessões)
            create_database: false // Não precisa criar, banco já existe
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

