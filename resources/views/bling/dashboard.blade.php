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
                                    <p class="text-xs text-gray-500">Gerenciar pedidos</p>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <button onclick="syncOrders()" class="w-full px-3 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded text-xs font-medium transition-colors">
                                    📦 Sincronizar Pedidos
                                </button>
                                <button onclick="fetchBlingStatuses()" class="w-full px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs font-medium transition-colors">
                                    🔍 Buscar Status do Bling
                                </button>
                                <button onclick="syncOrderStatuses()" class="w-full px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-medium transition-colors">
                                    🔄 Atualizar Status
                                </button>
                                <button onclick="clearStatusCache()" class="w-full px-3 py-2 bg-orange-100 hover:bg-orange-200 text-orange-700 rounded text-xs font-medium transition-colors">
                                    🗑️ Limpar Cache Status
                                </button>
                            </div>
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
                                <button onclick="sendCustomersToBling()" class="w-full px-3 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded text-xs font-medium transition-colors">
                                    📤 Enviar para Bling
                                </button>
                                <button onclick="getCustomersFromBling()" class="w-full px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded text-xs font-medium transition-colors">
                                    📥 Obter do Bling
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

                <!-- Pagamentos -->
                <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                    <div class="grid grid-cols-12 gap-0">
                        <div class="col-span-12 md:col-span-4 lg:col-span-3 p-4 border-r border-gray-200">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 bg-teal-100 rounded-lg flex items-center justify-center text-xl">
                                    💳
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 text-sm">Pagamentos</h3>
                                    <p class="text-xs text-gray-500">Formas de pagamento</p>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <button onclick="listPaymentMethods()" class="w-full px-3 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded text-xs font-medium transition-colors">
                                    📋 Listar Formas de Pagamento
                                </button>
                            </div>
                        </div>
                        <div class="col-span-12 md:col-span-8 lg:col-span-9 bg-gray-900">
                            <div class="flex justify-between items-center px-4 py-2 border-b border-gray-700">
                                <span class="text-teal-400 text-xs font-semibold">● Console Pagamentos</span>
                                <button onclick="clearPaymentsConsole()" class="text-gray-400 hover:text-white text-xs px-2 py-1 hover:bg-gray-800 rounded">
                                    🗑️
                                </button>
                            </div>
                            <div id="payments-console" class="p-4 text-white font-mono text-xs space-y-1 h-[250px] overflow-y-auto">
                                <div class="text-gray-500">Aguardando ação...</div>
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

            <!-- Consoles Separados de Webhooks -->
            <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- Console Bling -->
                <div class="bg-gray-950 rounded-lg shadow-lg border border-gray-700 overflow-hidden">
                    <div class="flex justify-between items-center px-4 py-3 border-b border-gray-700 bg-gray-900">
                        <div class="flex items-center gap-3">
                            <span class="text-indigo-400 text-sm font-bold">⚡ BLING WEBHOOKS</span>
                            <span class="text-gray-500 text-xs">(eventos automáticos)</span>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="clearBlingWebhooksConsole()" class="text-gray-400 hover:text-white text-xs px-2 py-1 hover:bg-gray-800 rounded">
                                🗑️ Limpar
                            </button>
                        </div>
                    </div>
                    <div id="bling-webhooks-console" class="p-4 text-white font-mono text-xs space-y-1 h-[400px] overflow-y-auto">
                        <div class="text-gray-500">Aguardando eventos do Bling...</div>
                        <div class="text-gray-600 text-xs mt-2">💡 URL: https://sanozukez-rodust-ecommerce.ultrahook.com/api/webhooks/bling</div>
                        <div class="text-green-600 text-xs mt-2">✅ Sistema de logs ativo</div>
                    </div>
                </div>

                <!-- Console Mercado Pago -->
                <div class="bg-gray-950 rounded-lg shadow-lg border border-gray-700 overflow-hidden">
                    <div class="flex justify-between items-center px-4 py-3 border-b border-gray-700 bg-gray-900">
                        <div class="flex items-center gap-3">
                            <span class="text-green-400 text-sm font-bold">💳 MERCADO PAGO WEBHOOKS</span>
                            <span class="text-gray-500 text-xs">(notificações de pagamento)</span>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="clearMercadoPagoWebhooksConsole()" class="text-gray-400 hover:text-white text-xs px-2 py-1 hover:bg-gray-800 rounded">
                                🗑️ Limpar
                            </button>
                        </div>
                    </div>
                    <div id="mercadopago-webhooks-console" class="p-4 text-white font-mono text-xs space-y-1 h-[400px] overflow-y-auto">
                        <div class="text-gray-500">Aguardando eventos do Mercado Pago...</div>
                        <div class="text-gray-600 text-xs mt-2">💡 Mercado Pago Webhook URL: <span class="text-white">https://sanozukez-mercadopago.ultrahook.com/api/webhooks/mercadopago</span></div>
                        <div class="text-green-600 text-xs mt-2">✅ Sistema de logs ativo</div>
                    </div>
                </div>

                <!-- Console Melhor Envio -->
                <div class="bg-gray-950 rounded-lg shadow-lg border border-gray-700 overflow-hidden">
                    <div class="flex justify-between items-center px-4 py-3 border-b border-gray-700 bg-gray-900">
                        <div class="flex items-center gap-3">
                            <span class="text-blue-400 text-sm font-bold">🚚 MELHOR ENVIO WEBHOOKS</span>
                            <span class="text-gray-500 text-xs">(notificações de envio)</span>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="clearMelhorEnvioWebhooksConsole()" class="text-gray-400 hover:text-white text-xs px-2 py-1 hover:bg-gray-800 rounded">
                                🗑️ Limpar
                            </button>
                        </div>
                    </div>
                    <div id="melhorenvio-webhooks-console" class="p-4 text-white font-mono text-xs space-y-1 h-[400px] overflow-y-auto">
                        <div class="text-gray-500">Aguardando eventos do Melhor Envio...</div>
                        <div class="text-gray-600 text-xs mt-2">💡 Melhor Envio Webhook URL: <span class="text-white">https://sanozukez-melhorenvio-webhook.ultrahook.com/api/melhor-envio/webhook</span></div>
                        <div class="text-green-600 text-xs mt-2">✅ Sistema de logs ativo</div>
                    </div>
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

        async function sendCustomersToBling() {
            const contentEl = document.getElementById('customers-console');
            
            if (!confirm('📤 ENVIAR CLIENTES PARA BLING\n\nIsso irá enviar clientes verificados do Laravel para o Bling.\nApenas clientes com email confirmado serão enviados.\n\nContinuar?')) {
                return;
            }
            
            contentEl.innerHTML = '<div class="text-yellow-400 animate-pulse">⏳ Enviando clientes para o Bling...</div>';
            
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
                        <div class="text-green-400 mb-2">✅ ${data.message}</div>
                        <div class="text-gray-400 text-xs space-y-1">
                            ${output.map(line => `<div>${line}</div>`).join('')}
                        </div>
                        <div class="text-blue-400 mt-2">📊 Total de clientes: ${data.total_customers}</div>
                        <div class="text-yellow-400 mt-2">⚙️ Execute o queue worker: php artisan queue:work</div>
                    `;
                } else {
                    contentEl.innerHTML = `<div class="text-red-400">❌ ${data.message || 'Erro ao enviar clientes'}</div>`;
                }
            } catch (error) {
                contentEl.innerHTML = `<div class="text-red-400">❌ Erro: ${error.message}</div>`;
            }
        }

        async function getCustomersFromBling() {
            const contentEl = document.getElementById('customers-console');
            
            if (!confirm('📥 OBTER CLIENTES DO BLING\n\nIsso irá buscar clientes com tipo "Cliente ecommerce" do Bling e criar/atualizar no Laravel.\n\nClientes novos serão criados sem senha e precisarão:\n• Usar Google OAuth, OU\n• Criar senha no primeiro acesso\n\nContinuar?')) {
                return;
            }
            
            const limit = prompt('Quantos clientes buscar? (máximo recomendado: 100)', '20');
            if (!limit) return;
            
            contentEl.innerHTML = '<div class="text-green-400 animate-pulse">⏳ Buscando clientes do Bling...</div>';
            
            try {
                const response = await fetch('/bling/api/get-customers-from-bling', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        limit: parseInt(limit)
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    let output = `<div class="text-green-400 mb-2">✅ ${data.message}</div>`;
                    output += `<div class="text-gray-400">─────────────────────────────</div>`;
                    output += `<div class="text-white mt-2">📊 Estatísticas:</div>`;
                    output += `<div class="text-green-400 ml-4">✅ Criados: ${data.stats.created}</div>`;
                    output += `<div class="text-blue-400 ml-4">🔄 Atualizados: ${data.stats.updated}</div>`;
                    output += `<div class="text-gray-400 ml-4">⏭️ Ignorados: ${data.stats.skipped}</div>`;
                    if (data.stats.errors > 0) {
                        output += `<div class="text-red-400 ml-4">❌ Erros: ${data.stats.errors}</div>`;
                    }
                    output += `<div class="text-yellow-400 mt-3">⚠️ Clientes novos precisam criar senha no primeiro acesso.</div>`;
                    output += `<div class="text-blue-400 mt-2">💡 Eles podem usar Google OAuth para fazer login automaticamente.</div>`;
                    contentEl.innerHTML = output;
                } else {
                    contentEl.innerHTML = `<div class="text-red-400">❌ ${data.message || 'Erro ao obter clientes'}</div>`;
                }
            } catch (error) {
                contentEl.innerHTML = `<div class="text-red-400">❌ Erro: ${error.message}</div>`;
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

        function clearPaymentsConsole() {
            document.getElementById('payments-console').innerHTML = '<div class="text-gray-500">Aguardando ação...</div>';
        }

        function clearBlingWebhooksConsole() {
            document.getElementById('bling-webhooks-console').innerHTML = '<div class="text-gray-500">Aguardando eventos do Bling...</div>';
        }

        function clearMercadoPagoWebhooksConsole() {
            document.getElementById('mercadopago-webhooks-console').innerHTML = '<div class="text-gray-500">Aguardando eventos do Mercado Pago...</div>';
            lastMercadoPagoLogId = 0;
        }

        function clearMelhorEnvioWebhooksConsole() {
            document.getElementById('melhorenvio-webhooks-console').innerHTML = '<div class="text-gray-500">Aguardando eventos do Melhor Envio...</div>';
            lastMelhorEnvioLogId = 0;
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
        
        /**
         * Listar formas de pagamento do Bling no console
         */
        async function listPaymentMethods() {
            const contentEl = document.getElementById('payments-console');
            contentEl.innerHTML = '<div class="text-teal-400">⏳ Consultando formas de pagamento...</div>';
            
            try {
                const response = await fetch('/bling/api/payment-methods', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    contentEl.innerHTML = `<div class="text-red-400">❌ Erro: ${data.message}</div>`;
                    return;
                }
                
                let output = '<div class="text-green-400 font-bold mb-2">✓ Formas de Pagamento do Bling</div>';
                output += '<div class="text-gray-400">─────────────────────────────────────────────────────</div>';
                
                data.payment_methods.forEach(method => {
                    const isDefault = method.padrao === 'S';
                    const marker = isDefault ? '<span class="text-yellow-400">★</span>' : '<span class="text-gray-500">○</span>';
                    const defaultLabel = isDefault ? ' <span class="text-yellow-300">[PADRÃO]</span>' : '';
                    const situacao = method.situacao === 'A' ? '<span class="text-green-400">Ativo</span>' : '<span class="text-red-400">Inativo</span>';
                    
                    output += `<div class="py-1">${marker} ID: <span class="text-blue-400">${method.id}</span> - ${method.descricao}${defaultLabel}</div>`;
                    output += `<div class="ml-4 text-gray-400 text-xs">Tipo: ${method.tipoPagamento} | Status: ${situacao} | Destino: ${method.tipoDestino}</div>`;
                });
                
                output += '<div class="text-gray-400 mt-2">─────────────────────────────────────────────────────</div>';
                output += `<div class="text-cyan-400 mt-2">💡 Total: ${data.payment_methods.length} forma(s) de pagamento</div>`;
                
                // Mostrar IDs configurados
                if (data.configured_methods) {
                    output += '<div class="text-gray-400 mt-3">Métodos Configurados (.env):</div>';
                    Object.entries(data.configured_methods).forEach(([key, value]) => {
                        const method = data.payment_methods.find(m => m.id == value);
                        if (method) {
                            output += `<div class="text-green-400 ml-2">✓ ${key.toUpperCase()}: ${method.descricao} (ID ${value})</div>`;
                        } else {
                            output += `<div class="text-yellow-400 ml-2">⚠ ${key.toUpperCase()}: ID ${value} (não encontrado)</div>`;
                        }
                    });
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


        // ========================================
        // STATUS DO BLING
        // ========================================
        
        async function fetchBlingStatuses() {
            const contentEl = document.getElementById('orders-console');
            const timestamp = new Date().toLocaleTimeString('pt-BR');
            
            contentEl.innerHTML = `<div class="text-yellow-400">[${timestamp}] 🔍 Buscando situações do Bling...</div>`;
            
            try {
                const response = await fetch('/bling/api/fetch-statuses', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();
                
                if (data.success) {
                    let output = contentEl.innerHTML;
                    output += `<div class="text-green-400">[${timestamp}] ✅ ${data.count} situações encontradas</div>`;
                    output += `<div class="text-blue-400 mt-2">[${timestamp}] 📋 Módulo de Vendas: ID ${data.module_id}</div>`;
                    output += `<div class="text-gray-400 mt-2">─────────────────────────────────────────────</div>`;
                    
                    Object.entries(data.statuses).forEach(([id, details]) => {
                        const color = details.internal_status === 'delivered' ? 'text-green-400' :
                                     details.internal_status === 'cancelled' ? 'text-red-400' :
                                     details.internal_status === 'shipped' ? 'text-indigo-400' :
                                     details.internal_status === 'invoiced' ? 'text-purple-400' :
                                     details.internal_status === 'processing' ? 'text-blue-400' :
                                     'text-yellow-400';
                        
                        output += `<div class="${color}">ID ${id}: ${details.nome} → ${details.internal_status}</div>`;
                    });
                    
                    output += `<div class="text-gray-400 mt-2">─────────────────────────────────────────────</div>`;
                    output += `<div class="text-green-400 mt-2">[${timestamp}] ✅ Status armazenados em cache (24h)</div>`;
                    contentEl.innerHTML = output;
                } else {
                    let output = contentEl.innerHTML;
                    output += `<div class="text-red-400">[${timestamp}] ❌ Erro: ${data.message}</div>`;
                    contentEl.innerHTML = output;
                }
                
                contentEl.scrollTop = contentEl.scrollHeight;
            } catch (error) {
                let output = contentEl.innerHTML;
                output += `<div class="text-red-400">[${timestamp}] ❌ Erro: ${error.message}</div>`;
                contentEl.innerHTML = output;
                contentEl.scrollTop = contentEl.scrollHeight;
            }
        }

        async function syncOrderStatuses() {
            const contentEl = document.getElementById('orders-console');
            const timestamp = new Date().toLocaleTimeString('pt-BR');
            
            contentEl.innerHTML = `<div class="text-yellow-400">[${timestamp}] 🔄 Sincronizando status de TODOS os pedidos...</div>`;
            
            try {
                const response = await fetch('/bling/api/sync-order-statuses', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();
                
                if (data.success) {
                    let output = contentEl.innerHTML;
                    output += `<div class="text-green-400">[${timestamp}] ✅ Sincronização concluída</div>`;
                    output += `<div class="text-cyan-400 ml-4">Total de pendentes: ${data.total}</div>`;
                    output += `<div class="text-green-400 ml-4">Sincronizados: ${data.synced}</div>`;
                    output += `<div class="text-red-400 ml-4">Falhas: ${data.failed}</div>`;
                    contentEl.innerHTML = output;
                } else {
                    let output = contentEl.innerHTML;
                    output += `<div class="text-red-400">[${timestamp}] ❌ Erro: ${data.message}</div>`;
                    contentEl.innerHTML = output;
                }
                
                contentEl.scrollTop = contentEl.scrollHeight;
            } catch (error) {
                let output = contentEl.innerHTML;
                output += `<div class="text-red-400">[${timestamp}] ❌ Erro: ${error.message}</div>`;
                contentEl.innerHTML = output;
                contentEl.scrollTop = contentEl.scrollHeight;
            }
        }

        async function clearStatusCache() {
            const contentEl = document.getElementById('orders-console');
            const timestamp = new Date().toLocaleTimeString('pt-BR');
            
            contentEl.innerHTML = `<div class="text-yellow-400">[${timestamp}] 🗑️ Limpando cache de status...</div>`;
            
            try {
                const response = await fetch('/bling/api/clear-status-cache', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();
                
                let output = contentEl.innerHTML;
                if (data.success) {
                    output += `<div class="text-green-400">[${timestamp}] ✅ Cache limpo com sucesso</div>`;
                    output += `<div class="text-gray-400 ml-4">Use "Buscar Status" para recarregar</div>`;
                } else {
                    output += `<div class="text-red-400">[${timestamp}] ❌ Erro: ${data.message}</div>`;
                }
                contentEl.innerHTML = output;
                contentEl.scrollTop = contentEl.scrollHeight;
            } catch (error) {
                let output = contentEl.innerHTML;
                output += `<div class="text-red-400">[${timestamp}] ❌ Erro: ${error.message}</div>`;
                contentEl.innerHTML = output;
                contentEl.scrollTop = contentEl.scrollHeight;
            }
        }

        function clearStatusConsole() {
            const contentEl = document.getElementById('orders-console');
            contentEl.innerHTML = '<div class="text-gray-500">Console limpo. Aguardando ação...</div>';
        }

        // ========================================
        // WEBHOOK LOGS - Polling em tempo real (separado por source)
        // ========================================
        
        let lastBlingLogId = 0;
        let lastMercadoPagoLogId = 0;
        let lastMelhorEnvioLogId = 0;
        let blingPollingInterval = null;
        let mercadoPagoPollingInterval = null;
        let melhorEnvioPollingInterval = null;
        
        async function loadBlingWebhookLogs() {
            try {
                const response = await fetch('/bling/api/webhook-logs?source=bling&limit=50');
                const data = await response.json();
                
                if (data.success && data.logs.length > 0) {
                    const contentEl = document.getElementById('bling-webhooks-console');
                    let newLogs = [];
                    
                    // Filtrar apenas logs novos
                    data.logs.forEach(log => {
                        if (log.id > lastBlingLogId) {
                            newLogs.push(log);
                        }
                    });
                    
                    if (newLogs.length > 0) {
                        // Atualizar último ID
                        lastBlingLogId = Math.max(...data.logs.map(l => l.id));
                        
                        // Adicionar novos logs ao console
                        newLogs.reverse().forEach(log => {
                            contentEl.innerHTML = formatWebhookLog(log) + contentEl.innerHTML;
                        });
                        
                        // Auto-scroll para o topo
                        contentEl.scrollTop = 0;
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar logs do Bling:', error);
            }
        }
        
        async function loadMercadoPagoWebhookLogs() {
            try {
                const response = await fetch('/bling/api/webhook-logs?source=mercadopago&limit=50');
                const data = await response.json();
                
                if (data.success && data.logs.length > 0) {
                    const contentEl = document.getElementById('mercadopago-webhooks-console');
                    let newLogs = [];
                    
                    // Filtrar apenas logs novos
                    data.logs.forEach(log => {
                        if (log.id > lastMercadoPagoLogId) {
                            newLogs.push(log);
                        }
                    });
                    
                    if (newLogs.length > 0) {
                        // Atualizar último ID
                        lastMercadoPagoLogId = Math.max(...data.logs.map(l => l.id));
                        
                        // Adicionar novos logs ao console
                        newLogs.reverse().forEach(log => {
                            contentEl.innerHTML = formatWebhookLog(log) + contentEl.innerHTML;
                        });
                        
                        // Auto-scroll para o topo
                        contentEl.scrollTop = 0;
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar logs do Mercado Pago:', error);
            }
        }
        
        async function loadMelhorEnvioWebhookLogs() {
            try {
                const response = await fetch('/bling/api/webhook-logs?source=melhorenvio&limit=50');
                const data = await response.json();
                
                if (data.success && data.logs.length > 0) {
                    const contentEl = document.getElementById('melhorenvio-webhooks-console');
                    let newLogs = [];
                    
                    // Filtrar apenas logs novos
                    data.logs.forEach(log => {
                        if (log.id > lastMelhorEnvioLogId) {
                            newLogs.push(log);
                        }
                    });
                    
                    if (newLogs.length > 0) {
                        // Atualizar último ID
                        lastMelhorEnvioLogId = Math.max(...data.logs.map(l => l.id));
                        
                        // Adicionar novos logs ao console
                        newLogs.reverse().forEach(log => {
                            contentEl.innerHTML = formatWebhookLog(log) + contentEl.innerHTML;
                        });
                        
                        // Auto-scroll para o topo
                        contentEl.scrollTop = 0;
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar logs do Melhor Envio:', error);
            }
        }
        
        function formatWebhookLog(log) {
            const timestamp = new Date(log.created_at).toLocaleTimeString('pt-BR');
            const statusColor = log.status === 'success' ? 'text-green-400' :
                              log.status === 'error' ? 'text-red-400' :
                              log.status === 'processing' ? 'text-yellow-400' :
                              'text-blue-400';
            
            const statusIcon = log.status === 'success' ? '✅' :
                              log.status === 'error' ? '❌' :
                              log.status === 'processing' ? '⏳' :
                              '📥';
            
            let logLine = `<div class="${statusColor} border-l-2 border-gray-700 pl-2 py-1">`;
            logLine += `<span class="text-gray-500">[${timestamp}]</span> `;
            logLine += `<span class="font-bold">${statusIcon} ${log.event_type || 'webhook'}</span>`;
            
            if (log.resource && log.action) {
                logLine += ` <span class="text-cyan-400">(${log.resource}.${log.action})</span>`;
            }
            
            if (log.response_code) {
                logLine += ` <span class="text-gray-400">HTTP ${log.response_code}</span>`;
            }
            
            if (log.error_message) {
                logLine += `<div class="text-red-300 text-xs ml-4 mt-1">⚠️ ${log.error_message}</div>`;
            }
            
            // Mostrar metadata relevante
            if (log.metadata) {
                if (log.metadata.product_id) {
                    logLine += `<div class="text-gray-400 text-xs ml-4">📦 Produto ID: ${log.metadata.product_id} (SKU: ${log.metadata.product_sku || 'N/A'})</div>`;
                }
                if (log.metadata.stock_updated) {
                    logLine += `<div class="text-gray-400 text-xs ml-4">📊 Estoque: ${log.metadata.stock_updated.old} → ${log.metadata.stock_updated.new}</div>`;
                }
                if (log.metadata.order_id) {
                    logLine += `<div class="text-gray-400 text-xs ml-4">🛒 Pedido: ${log.metadata.order_number || log.metadata.order_id}</div>`;
                }
                if (log.metadata.payment_id) {
                    logLine += `<div class="text-gray-400 text-xs ml-4">💳 Pagamento ID: ${log.metadata.payment_id}</div>`;
                }
                if (log.metadata.processing_time_ms) {
                    logLine += `<div class="text-gray-500 text-xs ml-4">⏱️ ${log.metadata.processing_time_ms}ms</div>`;
                }
            }
            
            logLine += `</div>`;
            return logLine;
        }
        
        function startWebhookPolling() {
            // Carregar logs iniciais
            loadBlingWebhookLogs().catch(err => console.error('Erro ao carregar logs Bling:', err));
            loadMercadoPagoWebhookLogs().catch(err => console.error('Erro ao carregar logs Mercado Pago:', err));
            loadMelhorEnvioWebhookLogs().catch(err => console.error('Erro ao carregar logs Melhor Envio:', err));
            
            // Polling a cada 2 segundos (separado por source)
            // Usar setTimeout recursivo para evitar problemas com listeners assíncronos
            function pollBling() {
                loadBlingWebhookLogs().catch(err => {
                    console.error('Erro no polling Bling:', err);
                }).finally(() => {
                    blingPollingInterval = setTimeout(pollBling, 2000);
                });
            }
            
            function pollMercadoPago() {
                loadMercadoPagoWebhookLogs().catch(err => {
                    console.error('Erro no polling Mercado Pago:', err);
                }).finally(() => {
                    mercadoPagoPollingInterval = setTimeout(pollMercadoPago, 2000);
                });
            }
            
            function pollMelhorEnvio() {
                loadMelhorEnvioWebhookLogs().catch(err => {
                    console.error('Erro no polling Melhor Envio:', err);
                }).finally(() => {
                    melhorEnvioPollingInterval = setTimeout(pollMelhorEnvio, 2000);
                });
            }
            
            pollBling();
            pollMercadoPago();
            pollMelhorEnvio();
        }
        
        function stopWebhookPolling() {
            if (blingPollingInterval) {
                clearTimeout(blingPollingInterval);
                blingPollingInterval = null;
            }
            if (mercadoPagoPollingInterval) {
                clearTimeout(mercadoPagoPollingInterval);
                mercadoPagoPollingInterval = null;
            }
            if (melhorEnvioPollingInterval) {
                clearTimeout(melhorEnvioPollingInterval);
                melhorEnvioPollingInterval = null;
            }
        }
        
        // Iniciar polling quando a página carregar
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', startWebhookPolling);
        } else {
            startWebhookPolling();
        }
        
        // Parar polling quando a página for fechada
        window.addEventListener('beforeunload', stopWebhookPolling);

        // Load status on page load
        checkStatus();
    </script>
</body>
</html>
