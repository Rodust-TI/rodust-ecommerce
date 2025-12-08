@extends('admin.layout')

@section('title', 'Editar Pedido')
@section('page-title', 'Editar Pedido #' . $order->order_number)
@section('page-description', 'Alterar informações do pedido')

@section('content')
<div class="space-y-6">
    <!-- Botões de Ação -->
    <div class="flex gap-2">
        <a href="{{ route('admin.orders.show', $order) }}" 
           class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
            ← Voltar
        </a>
    </div>

    <!-- Formulário -->
    <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="bg-gray-800 rounded-lg border border-gray-700 p-6">
        @csrf
        @method('PUT')

        <h3 class="text-lg font-semibold text-white mb-6">📋 Informações do Pedido</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Status do Pedido -->
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Status do Pedido *</label>
                <select name="status" 
                        required
                        class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach($orderStatuses as $status)
                        <option value="{{ $status->value }}" 
                                {{ old('status', $order->status) === $status->value ? 'selected' : '' }}>
                            {{ $status->icon() }} {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
                @error('status')
                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Status de Pagamento -->
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Status de Pagamento *</label>
                <select name="payment_status" 
                        required
                        class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach($paymentStatuses as $status)
                        <option value="{{ $status->value }}" 
                                {{ old('payment_status', $order->payment_status) === $status->value ? 'selected' : '' }}>
                            {{ $status->icon() }} {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
                @error('payment_status')
                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Observações -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-300 mb-2">Observações</label>
            <textarea name="notes" 
                      rows="4"
                      class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('notes', $order->notes) }}</textarea>
            @error('notes')
                <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Informações do Pedido (Somente Leitura) -->
        <div class="bg-gray-700/50 rounded-lg p-4 mb-6">
            <h4 class="text-sm font-medium text-gray-300 mb-3">Informações do Pedido (Somente Leitura)</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <label class="text-gray-400">Número do Pedido</label>
                    <p class="text-white">#{{ $order->order_number }}</p>
                </div>
                <div>
                    <label class="text-gray-400">Cliente</label>
                    <p class="text-white">{{ $order->customer->name ?? 'Cliente não encontrado' }}</p>
                </div>
                <div>
                    <label class="text-gray-400">Total</label>
                    <p class="text-white font-semibold">R$ {{ number_format($order->total, 2, ',', '.') }}</p>
                </div>
            </div>
        </div>

        <!-- Botões -->
        <div class="flex gap-2">
            <button type="submit" 
                    class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                💾 Salvar Alterações
            </button>
            <a href="{{ route('admin.orders.show', $order) }}" 
               class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection

