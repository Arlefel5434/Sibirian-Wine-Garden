<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $wine = Category::query()->firstOrCreate(
            ['slug' => 'wine'],
            ['name' => 'Wine', 'description' => 'Northern wines and limited harvest releases.'],
        );
        Category::query()->firstOrCreate(
            ['slug' => 'grapes'],
            ['name' => 'Grapes', 'description' => 'Fresh grapes and resilient varieties.'],
        );
        Category::query()->firstOrCreate(
            ['slug' => 'tools'],
            ['name' => 'Tools', 'description' => 'Equipment for the vineyard and cellar.'],
        );
        $alcohol = Attribute::query()->firstOrCreate(
            ['name' => 'Alcohol content'],
            ['type' => 'decimal', 'unit' => '%'],
        );
        $vintage = Attribute::query()->firstOrCreate(
            ['name' => 'Vintage'],
            ['type' => 'integer'],
        );
        Attribute::query()->firstOrCreate(
            ['name' => 'Origin'],
            ['type' => 'string'],
        );

        $product = Product::query()->firstOrCreate(
            ['slug' => 'northern-ice-wine'],
            [
                'name' => 'Northern Ice Wine',
                'description' => 'A small-batch dessert wine made from grapes gathered after the first deep frost.',
                'status' => 'active',
                'category_id' => $wine->id,
            ],
        );

        $product->skus()->updateOrCreate(
            ['sku' => 'ICE-WINE-375'],
            [
                'name' => '375 ml bottle',
                'stock_quantity' => 24,
                'price' => 3499,
                'currency' => 'EUR',
                'quantity_unit' => 'piece',
            ],
        );
        $product->skus()->updateOrCreate(
            ['sku' => 'ICE-WINE-750'],
            [
                'name' => '750 ml bottle',
                'stock_quantity' => 8,
                'price' => 6299,
                'currency' => 'EUR',
                'quantity_unit' => 'piece',
            ],
        );
        $product->attributeValues()->updateOrCreate(
            ['attribute_id' => $alcohol->id],
            ['value' => '12.5'],
        );
        $product->attributeValues()->updateOrCreate(
            ['attribute_id' => $vintage->id],
            ['value' => '2025'],
        );
    }
}
