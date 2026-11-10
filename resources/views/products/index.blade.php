@extends('layouts.react', [
    'title' => 'Products · Siberian Wine Garden',
    'themeColor' => '#211016',
    'assets' => ['resources/css/products.css', 'resources/client/views/products/ProductCatalogView.jsx'],
])

@section('content')
    <div id="products-root"></div>
@endsection
