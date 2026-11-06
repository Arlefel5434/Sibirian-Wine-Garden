<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#241017">
        <meta name="description" content="Siberian Wine Garden — northern wines, resilient grapes, and stories from the garden.">
        <x-favicon />
        <title>Siberian Wine Garden</title>
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/client/views/home/HomeView.jsx'])
    </head>
    <body>
        <div id="home-root"></div>
    </body>
</html>
