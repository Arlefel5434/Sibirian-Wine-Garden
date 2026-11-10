@extends('layouts.react', [
    'title' => $product->name.' · Siberian Wine Garden',
    'themeColor' => '#211016',
    'assets' => ['resources/css/products.css', 'resources/client/views/products/ProductView.jsx'],
])

@section('content')
    <div id="product-root" data-product-id="{{ $product->id }}"></div>
@endsection
