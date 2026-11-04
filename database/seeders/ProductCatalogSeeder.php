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
        $grapes = Category::query()->firstOrCreate(
            ['slug' => 'grapes'],
            ['name' => 'Grapes', 'description' => 'Fresh grapes and resilient varieties.'],
        );
        $tools = Category::query()->firstOrCreate(
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

        $grapeProduct = Product::query()->firstOrCreate(
            ['slug' => 'amur-grape-cuttings'],
            [
                'name' => 'Amur Grape Cuttings',
                'description' => 'Cold-hardy rooted cuttings selected for northern gardens and short growing seasons.',
                'status' => 'active',
                'category_id' => $grapes->id,
            ],
        );
        $grapeProduct->skus()->updateOrCreate(
            ['sku' => 'AMUR-CUTTING-3'],
            ['name' => 'Set of 3', 'stock_quantity' => 30, 'price' => 1899, 'currency' => 'EUR', 'quantity_unit' => 'piece'],
        );

        $pruner = Product::query()->firstOrCreate(
            ['slug' => 'vineyard-pruner'],
            [
                'name' => 'Vineyard Pruner',
                'description' => 'A balanced hand pruner for precise seasonal work among vines and young shoots.',
                'status' => 'active',
                'category_id' => $tools->id,
            ],
        );
        $pruner->skus()->updateOrCreate(
            ['sku' => 'PRUNER-STEEL'],
            ['name' => 'Forged steel', 'stock_quantity' => 18, 'price' => 4299, 'currency' => 'EUR', 'quantity_unit' => 'piece'],
        );

        $tastingSet = Product::query()->firstOrCreate(
            ['slug' => 'northern-tasting-set'],
            [
                'name' => 'Northern Tasting Set',
                'description' => 'A curated introduction to small-batch northern wines for a quiet evening at the table.',
                'status' => 'active',
                'category_id' => $wine->id,
            ],
        );
        $tastingSet->skus()->updateOrCreate(
            ['sku' => 'TASTING-SET-3'],
            ['name' => 'Three-bottle set', 'stock_quantity' => 12, 'price' => 8999, 'currency' => 'EUR', 'quantity_unit' => 'piece'],
        );
    }
}
