@extends('admin.layout')

@section('title', 'Detalhes do Pedido')
@section('page-title', 'Pedido #' . $order->order_number)
@section('page-description', 'Informações completas do pedido')

@section('content')
<div class="space-y-6">
    <!-- Botões de Ação -->
    <div class="flex gap-2">
        <a href="{{ route('admin.orders.index') }}" 
           class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
            ← Voltar
        </a>
        <a href="{{ route('admin.orders.edit', $order) }}" 
           class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
            ✏️ Editar Pedido
        </a>
    </div>

    <!-- Grid Principal -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Informações do Pedido -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Status e Pagamento -->
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-white mb-4">📋 Informações do Pedido</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-400">Número do Pedido</label>
                        <p class="text-white font-medium">#{{ $order->order_number }}</p>
                    </div>
                    @if($order->bling_order_number)
                        <div>
                            <label class="text-sm text-gray-400">Número Bling</label>
                            <p class="text-white font-medium">{{ $order->bling_order_number }}</p>
                        </div>
                    @endif
                    <div>
                        <label class="text-sm text-gray-400">Status</label>
                        <p class="text-white">
                            @php
                                $statusEnum = \App\Enums\OrderStatus::fromString($order->status);
                            @endphp
                            <span class="px-2 py-1 text-xs rounded bg-{{ $statusEnum?->color() ?? 'gray' }}-500/20 text-{{ $statusEnum?->color() ?? 'gray' }}-300">
                                {{ $statusEnum?->icon() ?? '❓' }} {{ $statusEnum?->label() ?? ucfirst($order->status) }}
                            </span>
                        </p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-400">Status de Pagamento</label>
                        <p class="text-white">
                            @php
                                $paymentStatusEnum = \App\Enums\PaymentStatus::fromString($order->payment_status);
                            @endphp
                            <span class="px-2 py-1 text-xs rounded bg-{{ $paymentStatusEnum?->color() ?? 'gray' }}-500/20 text-{{ $paymentStatusEnum?->color() ?? 'gray' }}-300">
                                {{ $paymentStatusEnum?->icon() ?? '❓' }} {{ $paymentStatusEnum?->label() ?? ucfirst($order->payment_status) }}
                            </span>
                        </p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-400">Data de Criação</label>
                        <p class="text-white">{{ $order->created_at->format('d/m/Y H:i:s') }}</p>
                    </div>
                    @if($order->paid_at)
                        <div>
                            <label class="text-sm text-gray-400">Data de Pagamento</label>
                            <p class="text-white">{{ $order->paid_at->format('d/m/Y H:i:s') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Itens do Pedido -->
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-white mb-4">🛍️ Itens do Pedido</h3>
                @if($order->items->count() > 0)
                    <div class="space-y-3">
                        @foreach($order->items as $item)
                            <div class="flex items-center justify-between p-4 bg-gray-700/50 rounded-lg">
                                <div class="flex-1">
                                    <p class="font-medium text-white">{{ $item->product_name }}</p>
                                    <p class="text-sm text-gray-400">SKU: {{ $item->product_sku }}</p>
                                    <p class="text-sm text-gray-400">Quantidade: {{ $item->quantity }} × R$ {{ number_format($item->unit_price, 2, ',', '.') }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-white">R$ {{ number_format($item->total_price, 2, ',', '.') }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-400">Nenhum item encontrado.</p>
                @endif
            </div>

            <!-- Endereço de Entrega -->
            @if($order->shipping_address)
                <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-white mb-4">📍 Endereço de Entrega</h3>
                    <div class="text-sm text-gray-300">
                        @php
                            $address = is_array($order->shipping_address) ? $order->shipping_address : json_decode($order->shipping_address, true);
                        @endphp
                        @if(is_array($address))
                            <p>{{ $address['address'] ?? '' }}, {{ $address['number'] ?? '' }}</p>
                            @if(!empty($address['complement']))
                                <p>{{ $address['complement'] }}</p>
                            @endif
                            <p>{{ $address['neighborhood'] ?? '' }}</p>
                            <p>{{ $address['city'] ?? '' }}/{{ $address['state'] ?? '' }}</p>
                            <p>CEP: {{ $address['zipcode'] ?? '' }}</p>
                        @else
                            <p>{{ $order->shipping_address }}</p>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Observações -->
            @if($order->notes)
                <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-white mb-4">📝 Observações</h3>
                    <p class="text-gray-300 whitespace-pre-wrap">{{ $order->notes }}</p>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Cliente -->
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-white mb-4">👤 Cliente</h3>
                @if($order->customer)
                    <div class="space-y-2">
                        <div>
                            <label class="text-sm text-gray-400">Nome</label>
                            <p class="text-white font-medium">{{ $order->customer->name }}</p>
                        </div>
                        <div>
                            <label class="text-sm text-gray-400">Email</label>
                            <p class="text-white">{{ $order->customer->email }}</p>
                        </div>
                        @if($order->customer->phone)
                            <div>
                                <label class="text-sm text-gray-400">Telefone</label>
                                <p class="text-white">{{ $order->customer->phone }}</p>
                            </div>
                        @endif
                        <div class="pt-2">
                            <a href="{{ route('admin.customers.show', $order->customer) }}" 
                               class="text-blue-400 hover:text-blue-300 text-sm">
                                Ver perfil completo →
                            </a>
                        </div>
                    </div>
                @else
                    <p class="text-gray-400">Cliente não encontrado.</p>
                @endif
            </div>

            <!-- Resumo Financeiro -->
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-white mb-4">💰 Resumo Financeiro</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Subtotal</span>
                        <span class="text-white">R$ {{ number_format($order->subtotal, 2, ',', '.') }}</span>
                    </div>
                    @if($order->discount > 0)
                        <div class="flex justify-between">
                            <span class="text-gray-400">Desconto</span>
                            <span class="text-red-400">- R$ {{ number_format($order->discount, 2, ',', '.') }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-gray-400">Frete</span>
                        <span class="text-white">R$ {{ number_format($order->shipping, 2, ',', '.') }}</span>
                    </div>
                    @if($order->payment_fee > 0)
                        <div class="flex justify-between">
                            <span class="text-gray-400">Taxa de Pagamento</span>
                            <span class="text-white">R$ {{ number_format($order->payment_fee, 2, ',', '.') }}</span>
                        </div>
                    @endif
                    <div class="pt-3 border-t border-gray-700 flex justify-between">
                        <span class="font-semibold text-white">Total</span>
                        <span class="font-bold text-green-400 text-lg">R$ {{ number_format($order->total, 2, ',', '.') }}</span>
                    </div>
                    @if($order->net_amount && $order->net_amount != $order->total)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Valor Líquido</span>
                            <span class="text-gray-400">R$ {{ number_format($order->net_amount, 2, ',', '.') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Informações de Pagamento -->
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-white mb-4">💳 Pagamento</h3>
                <div class="space-y-2">
                    <div>
                        <label class="text-sm text-gray-400">Método</label>
                        <p class="text-white">{{ ucfirst($order->payment_method ?? '-') }}</p>
                    </div>
                    @if($order->payment_id)
                        <div>
                            <label class="text-sm text-gray-400">ID Pagamento</label>
                            <p class="text-white font-mono text-xs">{{ $order->payment_id }}</p>
                        </div>
                    @endif
                    @if($order->installments > 1)
                        <div>
                            <label class="text-sm text-gray-400">Parcelas</label>
                            <p class="text-white">{{ $order->installments }}x</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Sincronização Bling -->
            @if($order->bling_order_number || $order->bling_synced_at)
                <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-white mb-4">🔗 Bling</h3>
                    <div class="space-y-2">
                        @if($order->bling_order_number)
                            <div>
                                <label class="text-sm text-gray-400">Pedido Bling</label>
                                <p class="text-white">{{ $order->bling_order_number }}</p>
                            </div>
                        @endif
                        @if($order->bling_synced_at)
                            <div>
                                <label class="text-sm text-gray-400">Última Sincronização</label>
                                <p class="text-white">{{ $order->bling_synced_at->format('d/m/Y H:i') }}</p>
                            </div>
                        @endif
                        @if($order->invoice_number)
                            <div>
                                <label class="text-sm text-gray-400">Nota Fiscal</label>
                                <p class="text-white">{{ $order->invoice_number }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

