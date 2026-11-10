<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Contracts\View\View;

class ProductPageController extends Controller
{
    public function __invoke(Product $product): View
    {
        return view('products.show', compact('product'));
    }
}
