@extends('layouts.react', [
    'title' => 'Siberian Wine Garden',
    'themeColor' => '#241017',
    'description' => 'Siberian Wine Garden — northern wines, resilient grapes, and stories from the garden.',
    'assets' => ['resources/css/app.css', 'resources/client/views/home/HomeView.jsx'],
])

@section('content')
    <div id="home-root"></div>
@endsection
