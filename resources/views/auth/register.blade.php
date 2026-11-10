@extends('layouts.react', [
    'title' => 'Create account · Siberian Wine Garden',
    'themeColor' => '#211016',
    'assets' => ['resources/css/auth.css', 'resources/client/views/auth/RegisterView.jsx'],
])

@section('content')
    <div id="auth-root"></div>
@endsection
