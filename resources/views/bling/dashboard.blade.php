<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Painel Bling - Rodust Ecommerce</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Integração Bling</h1>
                        <p class="text-sm text-gray-500 mt-1">Painel de gerenciamento ERP</p>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="window.location.reload()" class="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-lg">
                            🔄 Atualizar
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Status Card -->
            <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 mb-2">Status da Conexão</h2>
                        <div id="status-container" class="space-y-2">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full bg-gray-300 animate-pulse"></div>
                                <span class="text-gray-600">Verificando...</span>
                            </div>
                        </div>
                    </div>
                    <div id="action-buttons" class="hidden space-x-2">
                        <!-- Buttons will be inserted here by JavaScript -->
                    </div>
                </div>
            </div>

            <!-- Actions Grid -->
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Produtos -->
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center text-2xl">
                            📦
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Produtos</h3>
                            <p class="text-sm text-gray-500">Sincronizar catálogo</p>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <button onclick="listProducts()" class="w-full px-4 py-2 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg text-sm font-medium">
                            📋 Listar Produtos
                        </button>
                        <button onclick="syncProducts()" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">
                            🔄 Sincronizar Agora
                        </button>
                    </div>
                </div>

                <!-- Estoques -->
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center text-2xl">
                            📊
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Estoques</h3>
                            <p class="text-sm text-gray-500">Atualizar quantidades</p>
                        </div>
                    </div>
                    <button class="w-full px-4 py-2 bg-gray-300 text-gray-600 rounded-lg text-sm font-medium cursor-not-allowed" disabled>
                        Em Breve
                    </button>
                </div>

                <!-- Pedidos -->
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center text-2xl">
                            🛒
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Pedidos</h3>
                            <p class="text-sm text-gray-500">Enviar para Bling</p>
                        </div>
                    </div>
                    <button class="w-full px-4 py-2 bg-gray-300 text-gray-600 rounded-lg text-sm font-medium cursor-not-allowed" disabled>
                        Em Breve
                    </button>
                </div>

                <!-- Clientes -->
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Clientes</h3>
                            <p class="text-sm text-gray-500">Sincronizar cadastros</p>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <button onclick="syncCustomers()" class="w-full px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg text-sm font-medium transition-colors">
                            Sincronizar Agora
                        </button>
                        <button onclick="listContactTypes()" class="w-full px-4 py-2 bg-purple-100 hover:bg-purple-200 text-purple-700 rounded-lg text-sm font-medium transition-colors">
                            📋 Listar Tipos de Contato
                        </button>
                    </div>
                    <!-- Console de saída -->
                    <div id="customers-console" class="mt-4 p-3 bg-gray-900 text-green-400 rounded-lg text-xs font-mono h-32 overflow-y-auto hidden"></div>
                </div>

                <!-- Notas Fiscais -->
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center text-2xl">
                            📄
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Notas Fiscais</h3>
                            <p class="text-sm text-gray-500">Emitir NF-e</p>
                        </div>
                    </div>
                    <button class="w-full px-4 py-2 bg-gray-300 text-gray-600 rounded-lg text-sm font-medium cursor-not-allowed" disabled>
                        Em Breve
                    </button>
                </div>

                <!-- Webhooks -->
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center text-2xl">
                            ⚡
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Webhooks</h3>
                            <p class="text-sm text-gray-500">Eventos em tempo real</p>
                        </div>
                    </div>
                    <button class="w-full px-4 py-2 bg-gray-300 text-gray-600 rounded-lg text-sm font-medium cursor-not-allowed" disabled>
                        Em Breve
                    </button>
                </div>
            </div>

            <!-- Output Console -->
            <div id="console-output" class="hidden mt-6 bg-gray-900 rounded-lg p-6 text-white font-mono text-sm">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-green-400">Console:</span>
                    <button onclick="closeConsole()" class="text-gray-400 hover:text-white">✕</button>
                </div>
                <div id="console-content" class="space-y-1 max-h-96 overflow-y-auto">
                    <!-- Output will be inserted here -->
                </div>
            </div>
        </main>
    </div>

    <script>
        // Check authentication status on load
        async function checkStatus() {
            try {
                const response = await fetch('/bling/status');
                const data = await response.json();
                
                const container = document.getElementById('status-container');
                const actionsContainer = document.getElementById('action-buttons');
                
                if (data.authenticated) {
                    container.innerHTML = `
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-green-500"></div>
                            <span class="text-green-700 font-medium">Conectado</span>
                        </div>
                        <p class="text-sm text-gray-600 ml-5">${data.message}</p>
                    `;
                    
                    actionsContainer.innerHTML = `
                        <button onclick="revokeAuth()" class="px-4 py-2 text-sm text-red-600 hover:bg-red-50 border border-red-300 rounded-lg">
                            Desconectar
                        </button>
                        ${!data.access_token_valid && data.refresh_token_valid ? `
                        <button onclick="refreshToken()" class="px-4 py-2 text-sm text-yellow-600 hover:bg-yellow-50 border border-yellow-300 rounded-lg">
                            Renovar Token
                        </button>
                        ` : ''}
                    `;
                } else {
                    container.innerHTML = `
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-red-500"></div>
                            <span class="text-red-700 font-medium">Não Conectado</span>
                        </div>
                        <p class="text-sm text-gray-600 ml-5">${data.message}</p>
                    `;
                    
                    actionsContainer.innerHTML = `
                        <a href="/bling/authorize" class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                            Autorizar Bling
                        </a>
                    `;
                }
                
                actionsContainer.classList.remove('hidden');
            } catch (error) {
                console.error('Error checking status:', error);
            }
        }

        async function listProducts() {
            const consoleEl = document.getElementById('console-output');
            const contentEl = document.getElementById('console-content');
            
            consoleEl.classList.remove('hidden');
            contentEl.innerHTML = '<div class="text-yellow-400">⏳ Buscando produtos do Bling...</div>';
            
            try {
                const response = await fetch('/bling/api/products?limit=10');
                const data = await response.json();
                
                if (data.success) {
                    contentEl.innerHTML = `
                        <div class="text-green-400">✅ ${data.data.length} produtos encontrados:</div>
                        ${data.data.map(p => `
                            <div class="text-gray-300 border-l-2 border-gray-700 pl-3 py-1">
                                <div class="font-semibold">${p.nome}</div>
                                <div class="text-xs text-gray-500">ID: ${p.id} | SKU: ${p.codigo || 'N/A'}</div>
                            </div>
                        `).join('')}
                    `;
                } else {
                    contentEl.innerHTML = `<div class="text-red-400">❌ ${data.message || 'Erro ao buscar produtos'}</div>`;
                }
            } catch (error) {
                contentEl.innerHTML = `<div class="text-red-400">❌ Erro: ${error.message}</div>`;
            }
        }

        async function syncProducts() {
            const consoleEl = document.getElementById('console-output');
            const contentEl = document.getElementById('console-content');
            
            if (!confirm('Sincronizar produtos do Bling para o Laravel e WordPress?\n\nIsso pode demorar alguns minutos dependendo da quantidade de produtos.')) {
                return;
            }
            
            consoleEl.classList.remove('hidden');
            contentEl.innerHTML = '<div class="text-yellow-400 animate-pulse">⏳ Sincronizando produtos... Aguarde...</div>';
            
            try {
                const response = await fetch('/bling/api/sync-products', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        limit: 100,
                        force: false
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const output = data.output.split('\n').filter(line => line.trim());
                    contentEl.innerHTML = `
                        <div class="text-green-400 mb-2">✅ ${data.message}</div>
                        <div class="text-gray-400 text-xs space-y-1">
                            ${output.map(line => `<div>${line}</div>`).join('')}
                        </div>
                    `;
                } else {
                    contentEl.innerHTML = `<div class="text-red-400">❌ ${data.message || 'Erro ao sincronizar produtos'}</div>`;
                }
            } catch (error) {
                contentEl.innerHTML = `<div class="text-red-400">❌ Erro: ${error.message}</div>`;
            }
        }

        async function syncCustomers() {
            const consoleEl = document.getElementById('console-output');
            const contentEl = document.getElementById('console-content');
            
            if (!confirm('Sincronizar clientes verificados para o Bling?\n\nApenas clientes com email confirmado serão enviados.')) {
                return;
            }
            
            consoleEl.classList.remove('hidden');
            contentEl.innerHTML = '<div class="text-yellow-400 animate-pulse">Sincronizando clientes... Aguarde...</div>';
            
            try {
                const response = await fetch('/bling/api/sync-customers', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        limit: 100,
                        only_verified: true,
                        force: false
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const output = data.output.split('\n').filter(line => line.trim());
                    contentEl.innerHTML = `
                        <div class="text-green-400 mb-2">${data.message}</div>
                        <div class="text-gray-400 text-xs space-y-1">
                            ${output.map(line => `<div>${line}</div>`).join('')}
                        </div>
                        <div class="text-blue-400 mt-2">Total de clientes: ${data.total_customers}</div>
                        <div class="text-yellow-400 mt-2">Execute o queue worker para processar: php artisan queue:work</div>
                    `;
                } else {
                    contentEl.innerHTML = `<div class="text-red-400">Erro: ${data.message || 'Erro ao sincronizar clientes'}</div>`;
                }
            } catch (error) {
                contentEl.innerHTML = `<div class="text-red-400">Erro: ${error.message}</div>`;
            }
        }

        function closeConsole() {
            document.getElementById('console-output').classList.add('hidden');
        }

        async function revokeAuth() {
            if (!confirm('Deseja realmente desconectar do Bling? Será necessário autorizar novamente.')) return;
            
            try {
                const response = await fetch('/bling/revoke', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Desconectado com sucesso!');
                    window.location.reload();
                } else {
                    alert('Erro ao desconectar: ' + data.message);
                }
            } catch (error) {
                console.error('Revoke error:', error);
                alert('Erro ao desconectar: ' + error.message);
            }
        }

        async function refreshToken() {
            try {
                const response = await fetch('/bling/refresh', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    console.log('Token renovado automaticamente');
                    return true;
                } else {
                    console.error('Falha ao renovar token:', data.message);
                    return false;
                }
            } catch (error) {
                console.error('Erro ao renovar token:', error);
                return false;
            }
        }

        /**
         * Listar tipos de contato do Bling no console
         */
        async function listContactTypes() {
            const consoleEl = document.getElementById('customers-console');
            consoleEl.classList.remove('hidden');
            consoleEl.innerHTML = '<div class="text-yellow-400">⏳ Consultando tipos de contato...</div>';
            
            try {
                const response = await fetch('/bling/api/contact-types', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    consoleEl.innerHTML = `<div class="text-red-400">❌ Erro: ${data.message}</div>`;
                    return;
                }
                
                let output = '<div class="text-green-400 font-bold mb-2">✓ Tipos de Contato do Bling</div>';
                output += '<div class="text-gray-400">─────────────────────────────</div>';
                
                data.tipos.forEach(tipo => {
                    const isConfigured = tipo.id == data.configured_id;
                    const marker = isConfigured ? '<span class="text-green-400">✓</span>' : '<span class="text-gray-500">○</span>';
                    output += `<div class="py-1">${marker} ID: <span class="text-blue-400">${tipo.id}</span> - ${tipo.descricao}</div>`;
                });
                
                output += '<div class="text-gray-400 mt-2">─────────────────────────────</div>';
                output += `<div class="text-yellow-400 mt-2">ID Configurado: ${data.configured_id || 'Não configurado'}</div>`;
                
                if (data.cliente_ecommerce) {
                    output += `<div class="text-green-400">✓ Cliente ecommerce: ID ${data.cliente_ecommerce.id}</div>`;
                } else {
                    output += `<div class="text-red-400">⚠ "Cliente ecommerce" não encontrado</div>`;
                }
                
                consoleEl.innerHTML = output;
                
            } catch (error) {
                consoleEl.innerHTML = `<div class="text-red-400">❌ Erro: ${error.message}</div>`;
            }
        }

        // Load status on page load
        checkStatus();
    </script>
</body>
</html>
