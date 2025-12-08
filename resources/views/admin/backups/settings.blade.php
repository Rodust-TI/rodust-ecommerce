@extends('admin.layout')

@section('title', 'Configurações de Backup')
@section('page-title', 'Configurações')
@section('page-description', 'Configurar backups automáticos e nuvem')

@section('content')
<div class="space-y-6">
    <!-- Ferramentas de Banco de Dados -->
    <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-white mb-4">Ferramentas de Banco de Dados</h3>
        
        <div class="space-y-4">
            <div class="bg-gray-700 rounded-lg p-4">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <h4 class="font-medium text-white mb-2">🔄 Refresh do Banco de Dados</h4>
                        <p class="text-sm text-gray-400 mb-3">
                            Re-executa todas as migrations (equivalente a dropar e recriar tabelas).
                            <br>
                            <span class="text-yellow-400">⚠️ Isso irá remover TODOS os dados do banco!</span>
                        </p>
                        <div class="flex gap-2">
                            <button id="refresh-db-btn" 
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm transition-colors">
                                🔄 Executar Refresh
                            </button>
                            <button id="refresh-db-seed-btn" 
                                    class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm transition-colors">
                                🔄 Refresh + Seeders
                            </button>
                        </div>
                    </div>
                </div>
                <div id="refresh-messages" class="mt-4 hidden"></div>
            </div>
        </div>
    </div>

    <!-- Configurações de Backup -->
    <div class="bg-yellow-900/30 border border-yellow-700 rounded-lg p-4">
        <div class="flex items-start gap-3">
            <span class="text-2xl">🚧</span>
            <div>
                <h4 class="font-semibold text-yellow-400 mb-1">Em Desenvolvimento</h4>
                <p class="text-sm text-yellow-200">
                    Configurações de backups automáticos e nuvem serão implementadas em breve.
                </p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const refreshBtn = document.getElementById('refresh-db-btn');
    const refreshSeedBtn = document.getElementById('refresh-db-seed-btn');
    const messagesDiv = document.getElementById('refresh-messages');

    function showMessage(type, message) {
        const bgColor = type === 'success' ? 'bg-green-900 border-green-700 text-green-200' : 
                       type === 'error' ? 'bg-red-900 border-red-700 text-red-200' : 
                       'bg-blue-900 border-blue-700 text-blue-200';
        
        messagesDiv.className = bgColor + ' border-l-4 p-4 rounded-lg';
        messagesDiv.innerHTML = message;
        messagesDiv.classList.remove('hidden');
    }

    function refreshDatabase(seed = false) {
        if (!confirm('⚠️ ATENÇÃO: Isso irá:\n\n' +
                     '• Dropar TODAS as tabelas\n' +
                     '• Recriar todas as tabelas (migrations)\n' +
                     (seed ? '• Executar seeders\n' : '') +
                     '• REMOVER TODOS OS DADOS\n\n' +
                     'Esta ação NÃO pode ser desfeita!\n\n' +
                     'Deseja continuar?')) {
            return;
        }

        if (!confirm('🔴 ÚLTIMA CONFIRMAÇÃO\n\n' +
                     'Você tem CERTEZA?\n\n' +
                     'Todos os dados serão PERDIDOS!')) {
            return;
        }

        const btn = seed ? refreshSeedBtn : refreshBtn;
        const originalText = btn.textContent;
        
        btn.disabled = true;
        btn.textContent = '⏳ Executando...';
        messagesDiv.classList.add('hidden');

        fetch('{{ route("admin.backups.refresh-database") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ seed: seed })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('success', '✅ ' + data.message + '<br><pre class="text-xs mt-2">' + (data.output || '') + '</pre>');
                setTimeout(() => {
                    alert('Banco de dados atualizado! A página será recarregada.');
                    window.location.reload();
                }, 2000);
            } else {
                showMessage('error', '❌ ' + data.message);
                btn.disabled = false;
                btn.textContent = originalText;
            }
        })
        .catch(error => {
            showMessage('error', '❌ Erro ao atualizar banco: ' + error.message);
            btn.disabled = false;
            btn.textContent = originalText;
        });
    }

    refreshBtn.addEventListener('click', () => refreshDatabase(false));
    refreshSeedBtn.addEventListener('click', () => refreshDatabase(true));
});
</script>
@endpush
@endsection

