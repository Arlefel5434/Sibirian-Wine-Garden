@extends('layouts.react', [
    'title' => 'Purchase history · Siberian Wine Garden',
    'assets' => ['resources/css/cart.css', 'resources/client/views/orders/OrdersView.jsx'],
])

@section('content')
    <div id="orders-root"></div>
@endsection
