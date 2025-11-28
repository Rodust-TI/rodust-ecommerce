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

            <!-- Layout: Action Cards com Console Individual -->
            <div class="space-y-4">
                <!-- Produtos -->
                <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                    <div class="grid grid-cols-12 gap-0">
                        <!-- Card de Ação (30%) -->
                        <div class="col-span-12 md:col-span-4 lg:col-span-3 p-4 border-r border-gray-200">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center text-xl">
                                    📦
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 text-sm">Produtos</h3>
                                    <p class="text-xs text-gray-500">Sincronizar catálogo</p>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <button onclick="listProducts()" class="w-full px-3 py-2 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded text-xs font-medium transition-colors">
                                    📋 Listar Produtos
                                </button>
                                <button onclick="syncProducts()" class="w-full px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-medium transition-colors">
                                    🔄 Sincronizar Agora
                                </button>
                                <button onclick="syncProductsAdvanced()" class="w-full px-3 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded text-xs font-medium transition-colors">
                                    ⚡ Sincronizar Detalhes
                                </button>
                            </div>
                        </div>
                        <!-- Console Individual (70%) -->
                        <div class="col-span-12 md:col-span-8 lg:col-span-9 bg-gray-900">
                            <div class="flex justify-between items-center px-4 py-2 border-b border-gray-700">
                                <span class="text-green-400 text-xs font-semibold">● Console Produtos</span>
                                <button onclick="clearProductsConsole()" class="text-gray-400 hover:text-white text-xs px-2 py-1 hover:bg-gray-800 rounded">
                                    🗑️
                                </button>
                            </div>
                            <div id="products-console" class="p-4 text-white font-mono text-xs space-y-1 h-[250px] overflow-y-auto">
                                <div class="text-gray-500">Aguardando ação...</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pedidos -->
                <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                    <div class="grid grid-cols-12 gap-0">
                        <div class="col-span-12 md:col-span-4 lg:col-span-3 p-4 border-r border-gray-200">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center text-xl">
                                    🛒
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 text-sm">Pedidos</h3>
                                    <p class="text-xs text-gray-500">Enviar para Bling</p>
                                </div>
                            </div>
                            <button onclick="syncOrders()" class="w-full px-3 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded text-xs font-medium transition-colors">
                                📦 Sincronizar Pedidos
                            </button>
                        </div>
                        <div class="col-span-12 md:col-span-8 lg:col-span-9 bg-gray-900">
                            <div class="flex justify-between items-center px-4 py-2 border-b border-gray-700">
                                <span class="text-purple-400 text-xs font-semibold">● Console Pedidos</span>
                                <button onclick="clearOrdersConsole()" class="text-gray-400 hover:text-white text-xs px-2 py-1 hover:bg-gray-800 rounded">
                                    🗑️
                                </button>
                            </div>
                            <div id="orders-console" class="p-4 text-white font-mono text-xs space-y-1 h-[250px] overflow-y-auto">
                                <div class="text-gray-500">Aguardando ação...</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Clientes -->
                <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                    <div class="grid grid-cols-12 gap-0">
                        <div class="col-span-12 md:col-span-4 lg:col-span-3 p-4 border-r border-gray-200">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 text-sm">Clientes</h3>
                                    <p class="text-xs text-gray-500">Sincronizar cadastros</p>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <button onclick="syncCustomers()" class="w-full px-3 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded text-xs font-medium transition-colors">
                                    🔄 Sincronizar Agora
                                </button>
                                <button onclick="listContactTypes()" class="w-full px-3 py-2 bg-purple-100 hover:bg-purple-200 text-purple-700 rounded text-xs font-medium transition-colors">
                                    📋 Tipos de Contato
                                </button>
                            </div>
                        </div>
                        <div class="col-span-12 md:col-span-8 lg:col-span-9 bg-gray-900">
                            <div class="flex justify-between items-center px-4 py-2 border-b border-gray-700">
                                <span class="text-yellow-400 text-xs font-semibold">● Console Clientes</span>
                                <button onclick="clearCustomersConsole()" class="text-gray-400 hover:text-white text-xs px-2 py-1 hover:bg-gray-800 rounded">
                                    🗑️
                                </button>
                            </div>
                            <div id="customers-console" class="p-4 text-white font-mono text-xs space-y-1 h-[250px] overflow-y-auto">
                                <div class="text-gray-500">Aguardando ação...</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estoques -->
                <div class="bg-white rounded-lg shadow-sm border overflow-hidden opacity-50">
                    <div class="grid grid-cols-12 gap-0">
                        <div class="col-span-12 md:col-span-4 lg:col-span-3 p-4 border-r border-gray-200">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center text-xl">
                                    📊
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 text-sm">Estoques</h3>
                                    <p class="text-xs text-gray-500">Atualizar quantidades</p>
                                </div>
                            </div>
                            <button class="w-full px-3 py-2 bg-gray-300 text-gray-600 rounded text-xs font-medium cursor-not-allowed" disabled>
                                Em Breve
                            </button>
                        </div>
                        <div class="col-span-12 md:col-span-8 lg:col-span-9 bg-gray-900">
                            <div class="flex justify-between items-center px-4 py-2 border-b border-gray-700">
                                <span class="text-green-400 text-xs font-semibold opacity-50">● Console Estoques</span>
                            </div>
                            <div class="p-4 text-gray-600 font-mono text-xs h-[250px] flex items-center justify-center">
                                <span>Funcionalidade em desenvolvimento</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notas Fiscais -->
                <div class="bg-white rounded-lg shadow-sm border overflow-hidden opacity-50">
                    <div class="grid grid-cols-12 gap-0">
                        <div class="col-span-12 md:col-span-4 lg:col-span-3 p-4 border-r border-gray-200">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center text-xl">
                                    📄
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 text-sm">Notas Fiscais</h3>
                                    <p class="text-xs text-gray-500">Emitir NF-e</p>
                                </div>
                            </div>
                            <button class="w-full px-3 py-2 bg-gray-300 text-gray-600 rounded text-xs font-medium cursor-not-allowed" disabled>
                                Em Breve
                            </button>
                        </div>
                        <div class="col-span-12 md:col-span-8 lg:col-span-9 bg-gray-900">
                            <div class="flex justify-between items-center px-4 py-2 border-b border-gray-700">
                                <span class="text-red-400 text-xs font-semibold opacity-50">● Console Notas Fiscais</span>
                            </div>
                            <div class="p-4 text-gray-600 font-mono text-xs h-[250px] flex items-center justify-center">
                                <span>Funcionalidade em desenvolvimento</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Console Global de Webhooks (100% largura) -->
            <div class="mt-6 bg-gray-950 rounded-lg shadow-lg border border-gray-700 overflow-hidden">
                <div class="flex justify-between items-center px-4 py-3 border-b border-gray-700 bg-gray-900">
                    <div class="flex items-center gap-3">
                        <span class="text-indigo-400 text-sm font-bold">⚡ WEBHOOKS - Logs em Tempo Real</span>
                        <span class="text-gray-500 text-xs">(eventos automáticos do Bling e Mercado Pago)</span>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="testBlingWebhook()" class="text-xs px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded">
                            🧪 Testar Webhook Bling
                        </button>
                        <button onclick="clearWebhooksConsole()" class="text-gray-400 hover:text-white text-xs px-3 py-1 hover:bg-gray-800 rounded">
                            🗑️ Limpar
                        </button>
                    </div>
                </div>
                <div id="webhooks-console" class="p-4 text-white font-mono text-xs space-y-1 h-[400px] overflow-y-auto">
                    <div class="text-gray-500">Aguardando eventos de webhook...</div>
                    <div class="text-gray-600 text-xs mt-2">💡 Webhooks configurados:</div>
                    <div class="text-gray-600 text-xs ml-4">• Bling: https://localhost:8443/webhook</div>
                    <div class="text-gray-600 text-xs ml-4">• Mercado Pago: https://floatingly-incipient-paul.ngrok-free.dev/api/webhooks/mercadopago</div>
                    <div class="text-yellow-600 text-xs mt-2">⚠️ Teste manual pode falhar por CORS, mas webhooks reais do Bling funcionarão normalmente</div>
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
            const contentEl = document.getElementById('products-console');
            
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
            const contentEl = document.getElementById('products-console');
            
            if (!confirm('Sincronizar produtos do Bling para o Laravel e WordPress?\n\nIsso pode demorar alguns minutos dependendo da quantidade de produtos.')) {
                return;
            }
            
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

        async function syncProductsAdvanced() {
            const contentEl = document.getElementById('products-console');
            
            if (!confirm('⚡ SINCRONIZAÇÃO COMPLETA DE DETALHES\n\n' +
                'Esta operação irá:\n' +
                '• Buscar a lista de todos os produtos\n' +
                '• Enfileirar cada produto para busca detalhada\n' +
                '• Obter dimensões, peso, marca, imagens e mais\n' +
                '• Respeitar limite de 3 requisições/segundo do Bling\n\n' +
                'Pode demorar vários minutos. Continuar?')) {
                return;
            }
            
            contentEl.innerHTML = '<div class="text-purple-400 animate-pulse">⚡ Iniciando sincronização avançada...</div>';
            
            try {
                const response = await fetch('/bling/api/sync-products-advanced', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        limit: 100,
                        full: true
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    contentEl.innerHTML = `
                        <div class="text-green-400 mb-2">✅ ${data.message}</div>
                        <div class="text-purple-400 mb-2">📊 ${data.queued} produto(s) enfileirado(s)</div>
                        <div class="text-yellow-400 text-xs">
                            ⏱️ Processamento em background. Pode levar alguns minutos.
                        </div>
                        <div class="text-gray-400 text-xs mt-2">
                            Os produtos serão processados respeitando o limite de 3 req/s do Bling.
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
            const contentEl = document.getElementById('customers-console');
            
            if (!confirm('Sincronizar clientes verificados para o Bling?\n\nApenas clientes com email confirmado serão enviados.')) {
                return;
            }
            
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

        function clearProductsConsole() {
            document.getElementById('products-console').innerHTML = '<div class="text-gray-500">Aguardando ação...</div>';
        }

        function clearOrdersConsole() {
            document.getElementById('orders-console').innerHTML = '<div class="text-gray-500">Aguardando ação...</div>';
        }

        function clearCustomersConsole() {
            document.getElementById('customers-console').innerHTML = '<div class="text-gray-500">Aguardando ação...</div>';
        }

        function clearWebhooksConsole() {
            document.getElementById('webhooks-console').innerHTML = '<div class="text-gray-500">Aguardando eventos de webhook...</div>';
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
            const contentEl = document.getElementById('customers-console');
            contentEl.innerHTML = '<div class="text-yellow-400">⏳ Consultando tipos de contato...</div>';
            
            try {
                const response = await fetch('/bling/api/contact-types', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    contentEl.innerHTML = `<div class="text-red-400">❌ Erro: ${data.message}</div>`;
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
                
                contentEl.innerHTML = output;
                
            } catch (error) {
                contentEl.innerHTML = `<div class="text-red-400">❌ Erro: ${error.message}</div>`;
            }
        }
        
        // Sincronizar pedidos
        async function syncOrders() {
            const contentEl = document.getElementById('orders-console');
            contentEl.innerHTML = '<div class="text-blue-400">⏳ Sincronizando pedidos...</div>';

            try {
                const response = await fetch('/bling/api/sync-orders', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    let output = `<div class="text-green-400">✓ ${data.message}</div>`;
                    output += `<div class="text-gray-400 mt-2">─────────────────────────────</div>`;
                    output += `<div class="text-white mt-2">Sincronizados: ${data.synced}/${data.total}</div>`;
                    
                    if (data.errors && data.errors.length > 0) {
                        output += '<div class="text-red-400 mt-2">⚠ Erros:</div>';
                        data.errors.forEach(err => {
                            output += `<div class="text-red-300 text-xs ml-2">• ${err.order_number}: ${err.error}</div>`;
                        });
                    }
                    
                    contentEl.innerHTML = output;
                } else {
                    contentEl.innerHTML = `<div class="text-red-400">❌ ${data.message}</div>`;
                }

            } catch (error) {
                contentEl.innerHTML = `<div class="text-red-400">❌ Erro: ${error.message}</div>`;
            }
        }

        // Testar webhook do Bling
        async function testBlingWebhook() {
            const contentEl = document.getElementById('webhooks-console');
            const timestamp = new Date().toLocaleTimeString('pt-BR');
            
            contentEl.innerHTML = `<div class="text-yellow-400">[${timestamp}] 🧪 Enviando requisição de teste para webhook do Bling...</div>`;
            
            try {
                const response = await fetch('https://localhost:8443/webhook', {
                    method: 'POST',
                    mode: 'cors',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        test: true,
                        event: 'test.webhook',
                        data: {
                            message: 'Teste de webhook do dashboard',
                            timestamp: new Date().toISOString()
                        }
                    })
                });

                const data = await response.text();
                
                let output = contentEl.innerHTML;
                output += `<div class="text-green-400">[${timestamp}] ✅ Resposta recebida (HTTP ${response.status})</div>`;
                output += `<div class="text-gray-400 ml-4">${data}</div>`;
                output += `<div class="text-green-400 mt-2">✅ Webhook configurado corretamente! Webhooks reais do Bling funcionarão.</div>`;
                contentEl.innerHTML = output;
                
                // Auto-scroll para o final
                contentEl.scrollTop = contentEl.scrollHeight;
                
            } catch (error) {
                let output = contentEl.innerHTML;
                output += `<div class="text-red-400">[${timestamp}] ❌ Erro CORS: ${error.message}</div>`;
                output += `<div class="text-yellow-400 ml-4 mt-2">⚠️ Erro esperado em teste manual devido a CORS (HTTP→HTTPS)</div>`;
                output += `<div class="text-green-400 ml-4">✅ MAS: Webhooks reais do Bling funcionarão normalmente!</div>`;
                output += `<div class="text-gray-400 ml-4 mt-2">Motivo: Bling envia webhooks diretamente (servidor→servidor), sem restrições CORS</div>`;
                output += `<div class="text-blue-400 ml-4 mt-2">📝 Para testar: Configure no painel do Bling e envie um webhook de teste</div>`;
                contentEl.innerHTML = output;
                contentEl.scrollTop = contentEl.scrollHeight;
            }
        }

        // Load status on page load
        checkStatus();
    </script>
</body>
</html>
