@extends('layouts.react', [
    'title' => 'Test payment · Siberian Wine Garden',
    'assets' => ['resources/css/cart.css', 'resources/client/views/payments/PaymentSimulationView.jsx'],
])

@section('content')
    <div id="payment-simulation-root" data-payment-id="{{ $payment }}"></div>
@endsection
