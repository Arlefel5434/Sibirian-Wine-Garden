@extends('layouts.react', [
    'title' => 'Journal studio · Siberian Wine Garden',
    'themeColor' => '#211016',
    'assets' => ['resources/css/blog.css', 'resources/client/views/admin/BlogStudioView.jsx'],
])

@section('content')
    <div id="blog-root"></div>
@endsection
