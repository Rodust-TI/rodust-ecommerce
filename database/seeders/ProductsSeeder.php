<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'sku' => '123456789',
                'name' => 'Esmerilhadeira Angular',
                'description' => 'Esmerilhadeira angular profissional para trabalhos pesados',
                'price' => 299.90,
                'promotional_price' => null,
                'cost' => 150.00,
                'stock' => 10,
                'image' => 'https://via.placeholder.com/300x300?text=Esmerilhadeira',
                'images' => [],
                'active' => true,
                'bling_id' => '16565986803',
                'bling_category_id' => null,
                // Dimensões para frete
                'width' => 20,
                'height' => 15,
                'length' => 35,
                'weight' => 2.5,
                // Informações comerciais
                'brand' => 'Bosch',
                'free_shipping' => false,
                // Sincronização
                'last_sync_at' => now(),
                'sync_status' => 'synced',
            ],
            [
                'sku' => '300029B',
                'name' => 'Chave Fixa - 20x22mm - BELZER - 300029B',
                'description' => 'Chave fixa dupla 20x22mm Belzer, fabricada em aço cromo vanádio',
                'price' => 45.90,
                'promotional_price' => 39.90,
                'cost' => 25.00,
                'stock' => 50,
                'image' => 'https://via.placeholder.com/300x300?text=Chave+Fixa',
                'images' => [],
                'active' => true,
                'bling_id' => '16266436316',
                'bling_category_id' => null,
                // Dimensões para frete
                'width' => 3,
                'height' => 1,
                'length' => 25,
                'weight' => 0.3,
                // Informações comerciais
                'brand' => 'BELZER',
                'free_shipping' => false,
                // Sincronização
                'last_sync_at' => now(),
                'sync_status' => 'synced',
            ],
        ];

        foreach ($products as $productData) {
            Product::updateOrCreate(
                ['bling_id' => $productData['bling_id']],
                $productData
            );
        }

        $this->command->info('✅ 2 produtos de teste criados com sucesso!');
    }
}
