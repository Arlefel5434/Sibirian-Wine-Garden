@extends('layouts.react', [
    'title' => 'Favourites · Siberian Wine Garden',
    'themeColor' => '#211016',
    'assets' => ['resources/css/products.css', 'resources/client/views/favorites/FavoritesView.jsx'],
])

@section('content')
    <div id="favorites-root"></div>
@endsection
