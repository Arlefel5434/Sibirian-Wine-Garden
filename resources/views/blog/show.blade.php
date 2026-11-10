@extends('layouts.react', [
    'title' => $post->title.' · Siberian Wine Garden',
    'themeColor' => '#211016',
    'assets' => ['resources/css/post.css', 'resources/client/views/posts/PostView.jsx'],
])

@section('content')
    <div id="post-root" data-post-id="{{ $post->id }}"></div>
@endsection
