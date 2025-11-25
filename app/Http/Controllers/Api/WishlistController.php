<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    /**
     * Get customer's wishlist
     */
    public function index(Request $request)
    {
        $customer = Auth::guard('sanctum')->user();
        
        if (!$customer) {
            return response()->json(['message' => 'Não autenticado'], 401);
        }

        $wishlist = Wishlist::where('customer_id', $customer->id)
            ->with('product')
            ->get();

        return response()->json([
            'wishlist' => $wishlist->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product' => [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'price' => $item->product->price,
                        'image' => $item->product->image_url,
                        'slug' => $item->product->slug,
                        'stock' => $item->product->stock,
                    ],
                    'added_at' => $item->created_at->format('d/m/Y H:i'),
                ];
            }),
        ]);
    }

    /**
     * Add product to wishlist
     */
    public function store(Request $request)
    {
        $customer = Auth::guard('sanctum')->user();
        
        if (!$customer) {
            return response()->json(['message' => 'Não autenticado'], 401);
        }

        $validated = $request->validate([
            'product_id' => 'required|integer',
        ]);

        // Verificar se o produto existe
        $product = Product::find($validated['product_id']);
        
        if (!$product) {
            return response()->json([
                'message' => 'Produto não encontrado',
                'product_id_received' => $validated['product_id'],
            ], 404);
        }

        // Verificar se produto já está na wishlist
        $exists = Wishlist::where('customer_id', $customer->id)
            ->where('product_id', $validated['product_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Produto já está na sua lista de desejos',
                'already_exists' => true,
            ], 200);
        }

        $wishlist = Wishlist::create([
            'customer_id' => $customer->id,
            'product_id' => $validated['product_id'],
        ]);

        return response()->json([
            'message' => 'Produto adicionado à lista de desejos',
            'wishlist_item' => $wishlist,
        ], 201);
    }

    /**
     * Remove product from wishlist
     */
    public function destroy(Request $request, $productId)
    {
        $customer = Auth::guard('sanctum')->user();
        
        if (!$customer) {
            return response()->json(['message' => 'Não autenticado'], 401);
        }

        $deleted = Wishlist::where('customer_id', $customer->id)
            ->where('product_id', $productId)
            ->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Item não encontrado na wishlist'], 404);
        }

        return response()->json([
            'message' => 'Produto removido da lista de desejos',
        ]);
    }

    /**
     * Check if product is in wishlist
     */
    public function check(Request $request, $productId)
    {
        $customer = Auth::guard('sanctum')->user();
        
        if (!$customer) {
            return response()->json(['in_wishlist' => false]);
        }

        $inWishlist = Wishlist::where('customer_id', $customer->id)
            ->where('product_id', $productId)
            ->exists();

        return response()->json([
            'in_wishlist' => $inWishlist,
        ]);
    }
}
