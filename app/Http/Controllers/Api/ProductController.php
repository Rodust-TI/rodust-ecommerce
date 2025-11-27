<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Jobs\SyncProductToBling;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Product::where('active', true);

        // Filtro por categoria ou busca
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Paginação
        $perPage = $request->get('per_page', 15);
        $products = $query->paginate($perPage);

        return response()->json($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sku' => 'required|unique:products,sku',
            'name' => 'required|max:255',
            'description' => 'nullable',
            'price' => 'required|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|url',
            'active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product = Product::create($request->all());

        // Enviar para fila de sincronização com Bling
        SyncProductToBling::dispatch($product);

        return response()->json($product, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::findOrFail($id);
        return response()->json($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'sku' => 'sometimes|required|unique:products,sku,' . $id,
            'name' => 'sometimes|required|max:255',
            'price' => 'sometimes|required|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'stock' => 'sometimes|required|integer|min:0',
            'image' => 'nullable|url',
            'active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product->update($request->all());

        // Sincronizar com Bling após atualização
        SyncProductToBling::dispatch($product);

        return response()->json($product);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully'], 200);
    }

    /**
     * Sync products from Bling to Laravel
     */
    public function syncFromBling(Request $request)
    {
        try {
            $limit = $request->input('limit', 100);
            $force = $request->input('force', false);

            // Executar comando de sincronização
            \Illuminate\Support\Facades\Artisan::call('bling:sync-products', [
                '--limit' => $limit,
                '--force' => $force
            ]);

            $output = \Illuminate\Support\Facades\Artisan::output();

            // Buscar produtos após sincronização
            $products = Product::where('active', true)->get();

            return response()->json([
                'success' => true,
                'message' => 'Sincronização concluída com sucesso',
                'output' => $output,
                'total_products' => $products->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao sincronizar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all products with complete data for WordPress sync
     * Includes: dimensions, brand, promotional prices, images, etc.
     */
    public function wordpress(Request $request)
    {
        $perPage = $request->input('per_page', 50);
        $page = $request->input('page', 1);

        $products = Product::where('active', true)
            ->select([
                'id',
                'sku',
                'name',
                'description',
                'price',
                'promotional_price',
                'cost',
                'stock',
                'image',
                'images',
                'bling_id',
                'bling_category_id',
                // Dimensões
                'width',
                'height',
                'length',
                'weight',
                // Comercial
                'brand',
                'free_shipping',
                // Sincronização
                'last_sync_at',
                'sync_status',
                'created_at',
                'updated_at',
            ])
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'pagination' => [
                'total' => $products->total(),
                'per_page' => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'from' => $products->firstItem(),
                'to' => $products->lastItem(),
            ]
        ]);
    }

    /**
     * Sincronizar todos produtos ativos para WordPress
     */
    public function syncAllToWordPress(Request $request)
    {
        try {
            $products = Product::where('active', true)->get();

            if ($products->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum produto ativo encontrado'
                ], 404);
            }

            $queued = 0;
            $delaySeconds = 0;

            foreach ($products as $product) {
                // Enfileirar com delay de 200ms entre cada (evitar sobrecarga do WordPress)
                \App\Jobs\SyncProductToWordPress::dispatch($product->id)
                    ->delay(now()->addMilliseconds($delaySeconds * 200));
                
                $delaySeconds++;
                $queued++;
            }

            return response()->json([
                'success' => true,
                'message' => 'Sincronização com WordPress iniciada',
                'queued' => $queued,
                'estimated_time' => ceil($queued * 0.2) . ' segundos'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sincronizar produto específico para WordPress
     */
    public function syncOneToWordPress(Request $request, $id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produto não encontrado'
                ], 404);
            }

            \App\Jobs\SyncProductToWordPress::dispatch($product->id);

            return response()->json([
                'success' => true,
                'message' => "Sincronização do produto '{$product->name}' iniciada",
                'product_id' => $product->id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
