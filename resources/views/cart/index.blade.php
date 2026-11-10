@extends('layouts.react', [
    'title' => 'Your cart · Siberian Wine Garden',
    'assets' => ['resources/css/cart.css', 'resources/client/views/cart/CartView.jsx'],
])

@section('content')
    <div id="cart-root"></div>
@endsection
