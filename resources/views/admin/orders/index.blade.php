@extends('admin.layout')

@section('title', 'Gerenciar Pedidos')
@section('page-title', 'Pedidos')
@section('page-description', 'Listar e gerenciar pedidos do sistema')

@section('content')
<div class="space-y-6">
    <!-- Barra de Busca e Filtros -->
    <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
        <form method="GET" action="{{ route('admin.orders.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <!-- Busca -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Buscar</label>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}"
                           placeholder="Nº pedido, cliente, email..."
                           class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Filtro Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Status</label>
                    <select name="status" 
                            class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        @foreach($orderStatuses as $status)
                            <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filtro Pagamento -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Pagamento</label>
                    <select name="payment_status" 
                            class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        @foreach($paymentStatuses as $status)
                            <option value="{{ $status->value }}" {{ request('payment_status') === $status->value ? 'selected' : '' }}>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filtro Data -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Data</label>
                    <input type="date" 
                           name="date_from" 
                           value="{{ request('date_from') }}"
                           class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="flex gap-2">
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                    🔍 Buscar
                </button>
                <a href="{{ route('admin.orders.index') }}" 
                   class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
                    🔄 Limpar
                </a>
            </div>
        </form>
    </div>

    <!-- Resultados -->
    <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
        <div class="p-4 border-b border-gray-700 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-white">
                Pedidos ({{ $orders->total() }})
            </h3>
        </div>

        @if($orders->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Pedido</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Cliente</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Data</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Pagamento</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        @foreach($orders as $order)
                            @php
                                $statusInfo = [
                                    'pending' => ['color' => 'yellow', 'icon' => '⏳'],
                                    'processing' => ['color' => 'blue', 'icon' => '⚙️'],
                                    'invoiced' => ['color' => 'purple', 'icon' => '📄'],
                                    'shipped' => ['color' => 'indigo', 'icon' => '🚚'],
                                    'delivered' => ['color' => 'green', 'icon' => '✅'],
                                    'cancelled' => ['color' => 'red', 'icon' => '❌'],
                                ];
                                $status = $statusInfo[$order->status] ?? ['color' => 'gray', 'icon' => '❓'];
                                $paymentStatusInfo = [
                                    'pending' => ['color' => 'yellow', 'icon' => '⏳'],
                                    'approved' => ['color' => 'green', 'icon' => '✅'],
                                    'rejected' => ['color' => 'red', 'icon' => '❌'],
                                    'cancelled' => ['color' => 'gray', 'icon' => '🚫'],
                                ];
                                $paymentStatus = $paymentStatusInfo[$order->payment_status] ?? ['color' => 'gray', 'icon' => '❓'];
                            @endphp
                            <tr class="hover:bg-gray-700/50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-white">
                                        #{{ $order->order_number }}
                                    </div>
                                    @if($order->bling_order_number)
                                        <div class="text-xs text-gray-400">Bling: {{ $order->bling_order_number }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-white">{{ $order->customer->name ?? 'Cliente não encontrado' }}</div>
                                    <div class="text-xs text-gray-400">{{ $order->customer->email ?? '' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                    {{ $order->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded bg-{{ $status['color'] }}-500/20 text-{{ $status['color'] }}-300">
                                        {{ $status['icon'] }} {{ ucfirst($order->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded bg-{{ $paymentStatus['color'] }}-500/20 text-{{ $paymentStatus['color'] }}-300">
                                        {{ $paymentStatus['icon'] }} {{ ucfirst($order->payment_status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-white">
                                    R$ {{ number_format($order->total, 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex gap-2">
                                        <a href="{{ route('admin.orders.show', $order) }}" 
                                           class="text-blue-400 hover:text-blue-300">
                                            👁️ Ver
                                        </a>
                                        <a href="{{ route('admin.orders.edit', $order) }}" 
                                           class="text-yellow-400 hover:text-yellow-300">
                                            ✏️ Editar
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <div class="p-4 border-t border-gray-700">
                {{ $orders->links() }}
            </div>
        @else
            <div class="p-8 text-center">
                <p class="text-gray-400">Nenhum pedido encontrado.</p>
            </div>
        @endif
    </div>
</div>
@endsection

