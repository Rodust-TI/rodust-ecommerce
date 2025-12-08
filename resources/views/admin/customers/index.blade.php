@extends('admin.layout')

@section('title', 'Gerenciar Clientes')
@section('page-title', 'Clientes')
@section('page-description', 'Listar e gerenciar clientes do sistema')

@section('content')
<div class="space-y-6">
    <!-- Barra de Busca e Filtros -->
    <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
        <form method="GET" action="{{ route('admin.customers.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Busca -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Buscar</label>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}"
                           placeholder="Nome, email, CPF ou CNPJ..."
                           class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Filtro Tipo de Pessoa -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Tipo</label>
                    <select name="person_type" 
                            class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option value="F" {{ request('person_type') === 'F' ? 'selected' : '' }}>Pessoa Física</option>
                        <option value="J" {{ request('person_type') === 'J' ? 'selected' : '' }}>Pessoa Jurídica</option>
                    </select>
                </div>

                <!-- Filtro Email Verificado -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                    <select name="email_verified" 
                            class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option value="yes" {{ request('email_verified') === 'yes' ? 'selected' : '' }}>Verificado</option>
                        <option value="no" {{ request('email_verified') === 'no' ? 'selected' : '' }}>Não Verificado</option>
                    </select>
                </div>
            </div>

            <div class="flex gap-2">
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                    🔍 Buscar
                </button>
                <a href="{{ route('admin.customers.index') }}" 
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
                Clientes ({{ $customers->total() }})
            </h3>
        </div>

        @if($customers->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Cliente</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Documento</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Pedidos</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        @foreach($customers as $customer)
                            <tr class="hover:bg-gray-700/50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div>
                                            <div class="text-sm font-medium text-white">{{ $customer->name }}</div>
                                            @if($customer->email)
                                                <div class="text-sm text-gray-400">{{ $customer->email }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-300">
                                        @if($customer->cpf)
                                            <div>CPF: {{ $customer->cpf }}</div>
                                        @elseif($customer->cnpj)
                                            <div>CNPJ: {{ $customer->cnpj }}</div>
                                        @else
                                            <div class="text-gray-500">-</div>
                                        @endif
                                        @if($customer->phone)
                                            <div class="text-xs text-gray-400">📞 {{ $customer->phone }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded {{ $customer->person_type === 'F' ? 'bg-blue-500/20 text-blue-300' : 'bg-purple-500/20 text-purple-300' }}">
                                        {{ $customer->person_type === 'F' ? 'Pessoa Física' : 'Pessoa Jurídica' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                    {{ $customer->orders_count }} pedido(s)
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="space-y-1">
                                        @if($customer->email_verified_at)
                                            <span class="px-2 py-1 text-xs rounded bg-green-500/20 text-green-300">✅ Email Verificado</span>
                                        @else
                                            <span class="px-2 py-1 text-xs rounded bg-yellow-500/20 text-yellow-300">⏳ Email Não Verificado</span>
                                        @endif
                                        @if($customer->must_reset_password)
                                            <div class="px-2 py-1 text-xs rounded bg-orange-500/20 text-orange-300">🔑 Reset Senha</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex gap-2">
                                        <a href="{{ route('admin.customers.show', $customer) }}" 
                                           class="text-blue-400 hover:text-blue-300">
                                            👁️ Ver
                                        </a>
                                        <a href="{{ route('admin.customers.edit', $customer) }}" 
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
                {{ $customers->links() }}
            </div>
        @else
            <div class="p-8 text-center">
                <p class="text-gray-400">Nenhum cliente encontrado.</p>
            </div>
        @endif
    </div>
</div>
@endsection

