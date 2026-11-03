<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#211016">
        <title>{{ $product->name }} · Siberian Wine Garden</title>
        @viteReactRefresh
        @vite(['resources/css/products.css', 'resources/client/views/products/ProductView.jsx'])
    </head>
    <body>
        <div id="product-root" data-product-id="{{ $product->id }}"></div>
    </body>
</html>
