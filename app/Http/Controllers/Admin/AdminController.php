<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Models\Customer;
use App\Models\Integration;
use App\Models\Order;
use App\Models\Product;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminController extends Controller
{
    /**
     * Dashboard principal do painel administrativo
     * GET /admin
     */
    public function dashboard()
    {
        // Métricas básicas
        $metrics = [
            'total_customers' => Customer::count(),
            'total_orders' => Order::count(),
            'total_products' => Product::where('active', true)->count(),
            'total_revenue' => Order::where('payment_status', PaymentStatus::APPROVED->value)
                ->sum('total'),
        ];

        // Vendas dos últimos 30 dias
        $salesLast30Days = Order::where('payment_status', PaymentStatus::APPROVED->value)
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->sum('total');

        // Pedidos por status
        $ordersByStatus = Order::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status => $item->total];
            });

        // Pedidos por status de pagamento
        $ordersByPaymentStatus = Order::select('payment_status', DB::raw('count(*) as total'))
            ->groupBy('payment_status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->payment_status => $item->total];
            });

        // Status de integrações
        $integrations = [];
        
        // Bling (da tabela integrations)
        $blingIntegration = Integration::where('service', 'bling')->first();
        if ($blingIntegration) {
            $integrations[] = [
                'service' => 'Bling',
                'name' => 'Bling ERP',
                'is_active' => $blingIntegration->is_active,
                'token_expired' => $blingIntegration->isTokenExpired(),
                'last_sync_at' => $blingIntegration->last_sync_at?->diffForHumans(),
            ];
        } else {
            $integrations[] = [
                'service' => 'Bling',
                'name' => 'Bling ERP',
                'is_active' => false,
                'token_expired' => true,
                'last_sync_at' => null,
            ];
        }

        // Mercado Pago (verificar se está configurado no .env)
        $mercadoPagoConfigured = !empty(config('services.mercadopago.access_token_prod')) || 
                                  !empty(config('services.mercadopago.access_token_sandbox'));
        $integrations[] = [
            'service' => 'MercadoPago',
            'name' => 'Mercado Pago',
            'is_active' => $mercadoPagoConfigured,
            'token_expired' => false, // Mercado Pago usa API key, não token OAuth
            'last_sync_at' => null,
        ];

        // Melhor Envio (da tabela melhor_envio_settings)
        $melhorEnvio = \App\Models\MelhorEnvioSetting::getSettings();
        if ($melhorEnvio) {
            $integrations[] = [
                'service' => 'MelhorEnvio',
                'name' => 'Melhor Envio',
                'is_active' => !empty($melhorEnvio->access_token),
                'token_expired' => $melhorEnvio->isTokenExpired(),
                'last_sync_at' => null,
            ];
        } else {
            $integrations[] = [
                'service' => 'MelhorEnvio',
                'name' => 'Melhor Envio',
                'is_active' => false,
                'token_expired' => true,
                'last_sync_at' => null,
            ];
        }

        // Últimos backups
        $recentBackups = Backup::where('status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->limit(5)
            ->get();

        // Pedidos recentes
        $recentOrders = Order::with('customer')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'metrics',
            'salesLast30Days',
            'ordersByStatus',
            'ordersByPaymentStatus',
            'integrations',
            'recentBackups',
            'recentOrders'
        ));
    }

    /**
     * API: Dados para gráfico de vendas (últimos 30 dias)
     * GET /admin/api/sales-chart
     */
    public function salesChart()
    {
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now();

        // Vendas por dia
        $salesByDay = Order::where('payment_status', PaymentStatus::APPROVED->value)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('SUM(total) as total_revenue')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Preencher dias sem vendas com zero
        $chartData = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayData = $salesByDay->firstWhere('date', $dateStr);
            
            $chartData[] = [
                'date' => $currentDate->format('d/m'),
                'orders' => $dayData ? (int)$dayData->orders_count : 0,
                'revenue' => $dayData ? (float)$dayData->total_revenue : 0,
            ];
            
            $currentDate->addDay();
        }

        return response()->json([
            'success' => true,
            'data' => $chartData,
        ]);
    }
}

