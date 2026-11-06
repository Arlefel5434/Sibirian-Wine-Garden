<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#211016">
        <x-favicon />
        <title>{{ $post->title }} · Siberian Wine Garden</title>
        @viteReactRefresh
        @vite(['resources/css/post.css', 'resources/client/views/posts/PostView.jsx'])
    </head>
    <body>
        <div id="post-root" data-post-id="{{ $post->id }}"></div>
    </body>
</html>
