@extends('layouts.react', [
    'title' => 'Sign in · Siberian Wine Garden',
    'themeColor' => '#211016',
    'assets' => ['resources/css/auth.css', 'resources/client/views/auth/LoginView.jsx'],
])

@section('content')
    <div id="auth-root"></div>
@endsection
