@extends('admin.layout')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-description', 'Visão geral do sistema')

@section('content')
<div class="space-y-6">
    <!-- Cards de Métricas -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total de Clientes -->
        <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-lg border border-blue-500 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-blue-500/30 rounded-lg flex items-center justify-center text-2xl">
                    👥
                </div>
            </div>
            <div>
                <p class="text-blue-200 text-sm mb-1">Total de Clientes</p>
                <p class="text-3xl font-bold text-white">{{ number_format($metrics['total_customers'], 0, ',', '.') }}</p>
            </div>
        </div>

        <!-- Total de Pedidos -->
        <div class="bg-gradient-to-br from-purple-600 to-purple-700 rounded-lg border border-purple-500 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-purple-500/30 rounded-lg flex items-center justify-center text-2xl">
                    📦
                </div>
            </div>
            <div>
                <p class="text-purple-200 text-sm mb-1">Total de Pedidos</p>
                <p class="text-3xl font-bold text-white">{{ number_format($metrics['total_orders'], 0, ',', '.') }}</p>
            </div>
        </div>

        <!-- Total de Produtos -->
        <div class="bg-gradient-to-br from-green-600 to-green-700 rounded-lg border border-green-500 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-green-500/30 rounded-lg flex items-center justify-center text-2xl">
                    🛍️
                </div>
            </div>
            <div>
                <p class="text-green-200 text-sm mb-1">Produtos Ativos</p>
                <p class="text-3xl font-bold text-white">{{ number_format($metrics['total_products'], 0, ',', '.') }}</p>
            </div>
        </div>

        <!-- Receita Total -->
        <div class="bg-gradient-to-br from-yellow-600 to-yellow-700 rounded-lg border border-yellow-500 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-yellow-500/30 rounded-lg flex items-center justify-center text-2xl">
                    💰
                </div>
            </div>
            <div>
                <p class="text-yellow-200 text-sm mb-1">Receita Total</p>
                <p class="text-3xl font-bold text-white">R$ {{ number_format($metrics['total_revenue'], 2, ',', '.') }}</p>
                <p class="text-yellow-200 text-xs mt-1">Últimos 30 dias: R$ {{ number_format($salesLast30Days, 2, ',', '.') }}</p>
            </div>
        </div>
    </div>

    <!-- Gráfico de Vendas -->
    <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-white">📈 Vendas (Últimos 30 dias)</h3>
        </div>
        <div class="h-64">
            <canvas id="salesChart"></canvas>
        </div>
    </div>

    <!-- Grid: Status de Pedidos e Integrações -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Status de Pedidos -->
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-white mb-4">📊 Pedidos por Status</h3>
            <div class="space-y-3">
                @php
                    $statusLabels = [
                        'pending' => ['label' => 'Pendente', 'color' => 'yellow', 'icon' => '⏳'],
                        'processing' => ['label' => 'Em Processamento', 'color' => 'blue', 'icon' => '⚙️'],
                        'invoiced' => ['label' => 'Faturado', 'color' => 'purple', 'icon' => '📄'],
                        'shipped' => ['label' => 'Enviado', 'color' => 'indigo', 'icon' => '🚚'],
                        'delivered' => ['label' => 'Entregue', 'color' => 'green', 'icon' => '✅'],
                        'cancelled' => ['label' => 'Cancelado', 'color' => 'red', 'icon' => '❌'],
                    ];
                @endphp
                @foreach($statusLabels as $status => $info)
                    @php
                        $count = $ordersByStatus[$status] ?? 0;
                        $colorClasses = [
                            'yellow' => 'bg-yellow-500/20 text-yellow-300 border-yellow-500/30',
                            'blue' => 'bg-blue-500/20 text-blue-300 border-blue-500/30',
                            'purple' => 'bg-purple-500/20 text-purple-300 border-purple-500/30',
                            'indigo' => 'bg-indigo-500/20 text-indigo-300 border-indigo-500/30',
                            'green' => 'bg-green-500/20 text-green-300 border-green-500/30',
                            'red' => 'bg-red-500/20 text-red-300 border-red-500/30',
                        ];
                    @endphp
                    <div class="flex items-center justify-between p-3 rounded-lg border {{ $colorClasses[$info['color']] }}">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">{{ $info['icon'] }}</span>
                            <span class="font-medium">{{ $info['label'] }}</span>
                        </div>
                        <span class="font-bold">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Status de Integrações -->
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-white mb-4">🔗 Status de Integrações</h3>
            <div class="space-y-3">
                @forelse($integrations as $integration)
                    @php
                        $statusColor = $integration['is_active'] && !$integration['token_expired'] ? 'green' : 'red';
                        $statusText = $integration['is_active'] && !$integration['token_expired'] ? 'Conectado' : 'Desconectado';
                        $statusIcon = $integration['is_active'] && !$integration['token_expired'] ? '✅' : '❌';
                    @endphp
                    <div class="flex items-center justify-between p-3 rounded-lg border {{ $statusColor === 'green' ? 'bg-green-500/20 text-green-300 border-green-500/30' : 'bg-red-500/20 text-red-300 border-red-500/30' }}">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">{{ $statusIcon }}</span>
                            <div>
                                <span class="font-medium">{{ $integration['name'] ?? strtoupper($integration['service']) }}</span>
                                @if($integration['last_sync_at'])
                                    <p class="text-xs opacity-75">Última sync: {{ $integration['last_sync_at'] }}</p>
                                @endif
                            </div>
                        </div>
                        <span class="text-sm font-medium">{{ $statusText }}</span>
                    </div>
                @empty
                    <div class="text-center py-4 text-gray-400">
                        <p>Nenhuma integração configurada</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Grid: Últimos Backups e Pedidos Recentes -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Últimos Backups -->
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-white">💾 Últimos Backups</h3>
                <a href="{{ route('admin.backups.index') }}" class="text-sm text-blue-400 hover:text-blue-300">
                    Ver todos →
                </a>
            </div>
            <div class="space-y-2">
                @forelse($recentBackups as $backup)
                    <div class="flex items-center justify-between p-3 bg-gray-700/50 rounded-lg">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-white truncate">{{ $backup->filename }}</p>
                            <p class="text-xs text-gray-400">
                                {{ $backup->completed_at?->format('d/m/Y H:i') }} • {{ $backup->formatted_size }}
                            </p>
                        </div>
                        <span class="px-2 py-1 text-xs rounded {{ $backup->status === 'completed' ? 'bg-green-500/20 text-green-300' : 'bg-yellow-500/20 text-yellow-300' }}">
                            {{ ucfirst($backup->status) }}
                        </span>
                    </div>
                @empty
                    <div class="text-center py-4 text-gray-400">
                        <p>Nenhum backup encontrado</p>
                        <a href="{{ route('admin.backups.index') }}" class="text-blue-400 hover:text-blue-300 text-sm mt-2 inline-block">
                            Criar primeiro backup →
                        </a>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Pedidos Recentes -->
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-white">📦 Pedidos Recentes</h3>
                <a href="#" class="text-sm text-blue-400 hover:text-blue-300">
                    Ver todos →
                </a>
            </div>
            <div class="space-y-2">
                @forelse($recentOrders as $order)
                    @php
                        // Usar enum para obter label e ícone corretos
                        $statusEnum = \App\Enums\OrderStatus::fromString($order->status);
                        if ($statusEnum) {
                            $statusColor = $statusEnum->color();
                            $statusLabel = $statusEnum->label();
                            $statusIcon = $statusEnum->icon();
                        } else {
                            $statusColor = 'gray';
                            $statusLabel = ucfirst($order->status);
                            $statusIcon = '❓';
                        }
                    @endphp
                    <div class="flex items-center justify-between p-3 bg-gray-700/50 rounded-lg">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-white">
                                #{{ $order->order_number }}
                            </p>
                            <p class="text-xs text-gray-400">
                                {{ $order->customer->name ?? 'Cliente não encontrado' }} • 
                                {{ $order->created_at->format('d/m/Y H:i') }}
                            </p>
                        </div>
                        <div class="text-right ml-4">
                            <p class="text-sm font-bold text-white">R$ {{ number_format($order->total, 2, ',', '.') }}</p>
                            <span class="text-xs {{ $statusColor === 'green' ? 'text-green-300' : ($statusColor === 'red' ? 'text-red-300' : 'text-gray-400') }}">
                                {{ $statusIcon }} {{ $statusLabel }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-gray-400">
                        <p>Nenhum pedido encontrado</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Ações Rápidas -->
    <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-white mb-4">⚡ Ações Rápidas</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('admin.backups.index') }}" 
               class="flex items-center gap-3 p-4 bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors">
                <span class="text-2xl">💾</span>
                <div>
                    <div class="font-medium text-white">Criar Backup</div>
                    <div class="text-sm text-gray-400">Fazer backup manual do banco</div>
                </div>
            </a>
            
            <a href="{{ route('bling.dashboard') }}" 
               class="flex items-center gap-3 p-4 bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors">
                <span class="text-2xl">🔄</span>
                <div>
                    <div class="font-medium text-white">Sincronizar Bling</div>
                    <div class="text-sm text-gray-400">Acessar painel de integração</div>
                </div>
            </a>

            <a href="{{ route('admin.customers.index') }}" 
               class="flex items-center gap-3 p-4 bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors">
                <span class="text-2xl">👥</span>
                <div>
                    <div class="font-medium text-white">Gerenciar Clientes</div>
                    <div class="text-sm text-gray-400">Ver e editar clientes</div>
                </div>
            </a>

            <a href="{{ route('admin.orders.index') }}" 
               class="flex items-center gap-3 p-4 bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors">
                <span class="text-2xl">📦</span>
                <div>
                    <div class="font-medium text-white">Gerenciar Pedidos</div>
                    <div class="text-sm text-gray-400">Ver e editar pedidos</div>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Carregar dados do gráfico
    fetch('{{ route("admin.api.sales-chart") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const chartData = data.data;
                
                const ctx = document.getElementById('salesChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: chartData.map(item => item.date),
                        datasets: [
                            {
                                label: 'Receita (R$)',
                                data: chartData.map(item => item.revenue),
                                borderColor: 'rgb(59, 130, 246)',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                tension: 0.4,
                                yAxisID: 'y',
                            },
                            {
                                label: 'Pedidos',
                                data: chartData.map(item => item.orders),
                                borderColor: 'rgb(168, 85, 247)',
                                backgroundColor: 'rgba(168, 85, 247, 0.1)',
                                tension: 0.4,
                                yAxisID: 'y1',
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: {
                                    color: '#e2e8f0'
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: {
                                    color: '#9ca3af'
                                },
                                grid: {
                                    color: 'rgba(75, 85, 99, 0.3)'
                                }
                            },
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                ticks: {
                                    color: '#9ca3af',
                                    callback: function(value) {
                                        return 'R$ ' + value.toFixed(2);
                                    }
                                },
                                grid: {
                                    color: 'rgba(75, 85, 99, 0.3)'
                                }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                ticks: {
                                    color: '#9ca3af'
                                },
                                grid: {
                                    drawOnChartArea: false,
                                }
                            }
                        }
                    }
                });
            }
        })
        .catch(error => {
            console.error('Erro ao carregar dados do gráfico:', error);
        });
});
</script>
@endpush