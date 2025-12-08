@extends('admin.layout')

@section('title', 'Editar Cliente')
@section('page-title', 'Editar: ' . $customer->name)
@section('page-description', 'Alterar informações do cliente')

@section('content')
<div class="space-y-6">
    <!-- Botões de Ação -->
    <div class="flex gap-2">
        <a href="{{ route('admin.customers.show', $customer) }}" 
           class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
            ← Voltar
        </a>
    </div>

    <!-- Formulário -->
    <form method="POST" action="{{ route('admin.customers.update', $customer) }}" class="bg-gray-800 rounded-lg border border-gray-700 p-6">
        @csrf
        @method('PUT')

        <h3 class="text-lg font-semibold text-white mb-6">📋 Dados Básicos</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Nome Completo *</label>
                <input type="text" 
                       name="name" 
                       value="{{ old('name', $customer->name) }}"
                       required
                       class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('name')
                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Email *</label>
                <input type="email" 
                       name="email" 
                       value="{{ old('email', $customer->email) }}"
                       required
                       class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('email')
                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Telefone</label>
                <input type="text" 
                       name="phone" 
                       value="{{ old('phone', $customer->phone) }}"
                       class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Telefone Comercial</label>
                <input type="text" 
                       name="phone_commercial" 
                       value="{{ old('phone_commercial', $customer->phone_commercial) }}"
                       class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Tipo de Pessoa *</label>
                <select name="person_type" 
                        required
                        class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="F" {{ old('person_type', $customer->person_type) === 'F' ? 'selected' : '' }}>Pessoa Física</option>
                    <option value="J" {{ old('person_type', $customer->person_type) === 'J' ? 'selected' : '' }}>Pessoa Jurídica</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Email NFe</label>
                <input type="email" 
                       name="nfe_email" 
                       value="{{ old('nfe_email', $customer->nfe_email) }}"
                       class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <!-- Dados Pessoa Física -->
        <div id="person-fields" class="mb-6" style="display: {{ old('person_type', $customer->person_type) === 'F' ? 'block' : 'none' }}">
            <h3 class="text-lg font-semibold text-white mb-4">👤 Dados Pessoa Física</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">CPF</label>
                    <input type="text" 
                           name="cpf" 
                           value="{{ old('cpf', $customer->cpf) }}"
                           class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Data de Nascimento</label>
                    <input type="date" 
                           name="birth_date" 
                           value="{{ old('birth_date', $customer->birth_date?->format('Y-m-d')) }}"
                           class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <!-- Dados Pessoa Jurídica -->
        <div id="company-fields" class="mb-6" style="display: {{ old('person_type', $customer->person_type) === 'J' ? 'block' : 'none' }}">
            <h3 class="text-lg font-semibold text-white mb-4">🏢 Dados Pessoa Jurídica</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">CNPJ</label>
                    <input type="text" 
                           name="cnpj" 
                           value="{{ old('cnpj', $customer->cnpj) }}"
                           class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Nome Fantasia</label>
                    <input type="text" 
                           name="fantasy_name" 
                           value="{{ old('fantasy_name', $customer->fantasy_name) }}"
                           class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Inscrição Estadual</label>
                    <input type="text" 
                           name="state_registration" 
                           value="{{ old('state_registration', $customer->state_registration) }}"
                           class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">UF</label>
                    <input type="text" 
                           name="state_uf" 
                           value="{{ old('state_uf', $customer->state_uf) }}"
                           maxlength="2"
                           class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Tipo de Contribuinte</label>
                    <input type="text" 
                           name="taxpayer_type" 
                           value="{{ old('taxpayer_type', $customer->taxpayer_type) }}"
                           class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <!-- Alterar Senha -->
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-white mb-4">🔐 Alterar Senha</h3>
            <div class="bg-yellow-900/30 border border-yellow-700 rounded-lg p-4 mb-4">
                <p class="text-sm text-yellow-200">Deixe em branco para manter a senha atual.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Nova Senha</label>
                    <input type="password" 
                           name="password" 
                           class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('password')
                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Confirmar Senha</label>
                    <input type="password" 
                           name="password_confirmation" 
                           class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <!-- Botões -->
        <div class="flex gap-2">
            <button type="submit" 
                    class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                💾 Salvar Alterações
            </button>
            <a href="{{ route('admin.customers.show', $customer) }}" 
               class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
                Cancelar
            </a>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const personTypeSelect = document.querySelector('select[name="person_type"]');
    const personFields = document.getElementById('person-fields');
    const companyFields = document.getElementById('company-fields');

    personTypeSelect.addEventListener('change', function() {
        if (this.value === 'F') {
            personFields.style.display = 'block';
            companyFields.style.display = 'none';
        } else {
            personFields.style.display = 'none';
            companyFields.style.display = 'block';
        }
    });
});
</script>
@endpush
@endsection

