@extends('admin.layout')

@section('title', 'Detalhes do Cliente')
@section('page-title', 'Cliente: ' . $customer->name)
@section('page-description', 'Informações completas do cliente')

@section('content')
<div class="space-y-6">
    <!-- Botões de Ação -->
    <div class="flex gap-2">
        <a href="{{ route('admin.customers.index') }}" 
           class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
            ← Voltar
        </a>
        <a href="{{ route('admin.customers.edit', $customer) }}" 
           class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
            ✏️ Editar Cliente
        </a>
    </div>

    <!-- Informações Principais -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Dados Pessoais -->
        <div class="lg:col-span-2 bg-gray-800 rounded-lg border border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-white mb-4">📋 Dados Pessoais</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm text-gray-400">Nome Completo</label>
                    <p class="text-white font-medium">{{ $customer->name }}</p>
                </div>
                <div>
                    <label class="text-sm text-gray-400">Email</label>
                    <p class="text-white font-medium">
                        {{ $customer->email }}
                        @if($customer->email_verified_at)
                            <span class="text-green-400 text-xs ml-2">✅ Verificado</span>
                        @else
                            <span class="text-yellow-400 text-xs ml-2">⏳ Não Verificado</span>
                        @endif
                    </p>
                </div>
                <div>
                    <label class="text-sm text-gray-400">Telefone</label>
                    <p class="text-white">{{ $customer->phone ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm text-gray-400">Telefone Comercial</label>
                    <p class="text-white">{{ $customer->phone_commercial ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm text-gray-400">Tipo de Pessoa</label>
                    <p class="text-white">
                        <span class="px-2 py-1 text-xs rounded {{ $customer->person_type === 'F' ? 'bg-blue-500/20 text-blue-300' : 'bg-purple-500/20 text-purple-300' }}">
                            {{ $customer->person_type === 'F' ? 'Pessoa Física' : 'Pessoa Jurídica' }}
                        </span>
                    </p>
                </div>
                @if($customer->person_type === 'F')
                    <div>
                        <label class="text-sm text-gray-400">CPF</label>
                        <p class="text-white">{{ $customer->cpf ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-400">Data de Nascimento</label>
                        <p class="text-white">{{ $customer->birth_date?->format('d/m/Y') ?? '-' }}</p>
                    </div>
                @else
                    <div>
                        <label class="text-sm text-gray-400">CNPJ</label>
                        <p class="text-white">{{ $customer->cnpj ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-400">Nome Fantasia</label>
                        <p class="text-white">{{ $customer->fantasy_name ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-400">Inscrição Estadual</label>
                        <p class="text-white">{{ $customer->state_registration ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-400">Tipo de Contribuinte</label>
                        <p class="text-white">{{ $customer->taxpayer_type ?? '-' }}</p>
                    </div>
                @endif
                <div>
                    <label class="text-sm text-gray-400">Email NFe</label>
                    <p class="text-white">{{ $customer->nfe_email ?? '-' }}</p>
                </div>
            </div>
        </div>

        <!-- Estatísticas -->
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-white mb-4">📊 Estatísticas</h3>
            <div class="space-y-4">
                <div>
                    <label class="text-sm text-gray-400">Total de Pedidos</label>
                    <p class="text-2xl font-bold text-white">{{ $stats['total_orders'] }}</p>
                </div>
                <div>
                    <label class="text-sm text-gray-400">Total Gasto</label>
                    <p class="text-2xl font-bold text-green-400">R$ {{ number_format($stats['total_spent'], 2, ',', '.') }}</p>
                </div>
                <div>
                    <label class="text-sm text-gray-400">Ticket Médio</label>
                    <p class="text-xl font-semibold text-white">R$ {{ number_format($stats['average_order_value'] ?? 0, 2, ',', '.') }}</p>
                </div>
                @if($stats['last_order'])
                    <div>
                        <label class="text-sm text-gray-400">Último Pedido</label>
                        <p class="text-sm text-white">
                            #{{ $stats['last_order']->order_number }}<br>
                            <span class="text-gray-400">{{ $stats['last_order']->created_at->format('d/m/Y') }}</span>
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Endereços -->
    <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-white mb-4">📍 Endereços</h3>
        @if($customer->addresses->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($customer->addresses as $address)
                    <div class="p-4 bg-gray-700/50 rounded-lg border border-gray-600">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <h4 class="font-medium text-white">
                                    {{ $address->label ?? 'Endereço' }}
                                    @if($address->is_shipping && $address->is_billing)
                                        <span class="text-xs text-blue-400">(Entrega e Cobrança)</span>
                                    @elseif($address->is_shipping)
                                        <span class="text-xs text-green-400">(Entrega)</span>
                                    @elseif($address->is_billing)
                                        <span class="text-xs text-purple-400">(Cobrança)</span>
                                    @endif
                                </h4>
                                @if($address->recipient_name)
                                    <p class="text-sm text-gray-400">Destinatário: {{ $address->recipient_name }}</p>
                                @endif
                            </div>
                        </div>
                        <p class="text-sm text-gray-300">
                            {{ $address->address }}, {{ $address->number }}<br>
                            @if($address->complement){{ $address->complement }}, @endif
                            {{ $address->neighborhood }}<br>
                            {{ $address->city }}/{{ $address->state }}<br>
                            CEP: {{ $address->formatted_zipcode }}
                        </p>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-400">Nenhum endereço cadastrado.</p>
        @endif
    </div>

    <!-- Últimos Pedidos -->
    <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-white mb-4">📦 Últimos Pedidos</h3>
        @if($customer->orders->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-300 uppercase">Pedido</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-300 uppercase">Data</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-300 uppercase">Status</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-300 uppercase">Pagamento</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-300 uppercase">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        @foreach($customer->orders as $order)
                            <tr class="hover:bg-gray-700/50">
                                <td class="px-4 py-2 text-sm text-white">#{{ $order->order_number }}</td>
                                <td class="px-4 py-2 text-sm text-gray-300">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-2">
                                    @php
                                        $statusEnum = \App\Enums\OrderStatus::fromString($order->status);
                                        $statusColor = $statusEnum ? $statusEnum->color() : 'gray';
                                        $statusLabel = $statusEnum ? $statusEnum->label() : ucfirst($order->status);
                                    @endphp
                                    <span class="px-2 py-1 text-xs rounded bg-{{ $statusColor }}-500/20 text-{{ $statusColor }}-300">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 text-xs rounded {{ $order->payment_status === 'approved' ? 'bg-green-500/20 text-green-300' : 'bg-yellow-500/20 text-yellow-300' }}">
                                        {{ ucfirst($order->payment_status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-sm font-medium text-white">R$ {{ number_format($order->total, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-gray-400">Nenhum pedido encontrado.</p>
        @endif
    </div>

    <!-- Informações de Sincronização -->
    @if($customer->bling_id)
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-white mb-4">🔗 Sincronização Bling</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm text-gray-400">ID Bling</label>
                    <p class="text-white font-mono">{{ $customer->bling_id }}</p>
                </div>
                <div>
                    <label class="text-sm text-gray-400">Última Sincronização</label>
                    <p class="text-white">{{ $customer->bling_synced_at?->format('d/m/Y H:i') ?? '-' }}</p>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

