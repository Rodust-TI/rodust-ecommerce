<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Painel Administrativo') - Rodust Ecommerce</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Tema escuro padrão Laravel */
        body {
            background-color: #1a202c;
            color: #e2e8f0;
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-100">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-800 border-r border-gray-700">
            <div class="p-6">
                <h1 class="text-xl font-bold text-white mb-8">Painel Admin</h1>
                
                <nav class="space-y-2">
                    <a href="{{ route('admin.dashboard') }}" 
                       class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('admin.dashboard') ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                        <span class="text-xl">📊</span>
                        <span>Dashboard</span>
                    </a>
                    
                    <a href="{{ route('admin.customers.index') }}" 
                       class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('admin.customers.*') ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                        <span class="text-xl">👥</span>
                        <span>Clientes</span>
                    </a>
                    
                    <a href="{{ route('admin.orders.index') }}" 
                       class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('admin.orders.*') ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                        <span class="text-xl">📦</span>
                        <span>Pedidos</span>
                    </a>
                    
                    <a href="{{ route('admin.backups.index') }}" 
                       class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('admin.backups.*') ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                        <span class="text-xl">💾</span>
                        <span>Backups</span>
                    </a>
                    
                    <a href="{{ route('bling.dashboard') }}" 
                       class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white">
                        <span class="text-xl">🔗</span>
                        <span>Integração Bling</span>
                    </a>
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1">
            <!-- Header -->
            <header class="bg-gray-800 border-b border-gray-700 px-6 py-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-white">@yield('page-title', 'Dashboard')</h2>
                        <p class="text-sm text-gray-400 mt-1">@yield('page-description', 'Painel administrativo')</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-gray-400">
                            {{ now()->format('d/m/Y H:i') }}
                        </span>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="p-6">
                @yield('content')
            </div>
        </main>
    </div>

    @stack('scripts')
    
    <!-- Pagination Styles -->
    <style>
        .pagination {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        .pagination a, .pagination span {
            padding: 0.5rem 0.75rem;
            background: #374151;
            color: #e5e7eb;
            border-radius: 0.375rem;
            text-decoration: none;
        }
        .pagination a:hover {
            background: #4b5563;
        }
        .pagination .current {
            background: #3b82f6;
            color: white;
        }
    </style>
</body>
</html>

