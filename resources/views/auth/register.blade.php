<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#211016">
        <x-favicon />
        <title>Create account · Siberian Wine Garden</title>
        @viteReactRefresh
        @vite(['resources/css/auth.css', 'resources/client/views/auth/RegisterView.jsx'])
    </head>
    <body>
        <div id="auth-root"></div>
    </body>
</html>
