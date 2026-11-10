<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @isset($themeColor)
            <meta name="theme-color" content="{{ $themeColor }}">
        @endisset
        @isset($description)
            <meta name="description" content="{{ $description }}">
        @endisset
        <x-favicon />
        <title>{{ $title }}</title>
        @viteReactRefresh
        @vite($assets)
    </head>
    <body>
        @yield('content')
    </body>
</html>
