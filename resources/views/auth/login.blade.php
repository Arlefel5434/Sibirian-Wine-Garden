<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#211016">
        <x-favicon />
        <title>Sign in · Siberian Wine Garden</title>
        @viteReactRefresh
        @vite(['resources/css/auth.css', 'resources/client/views/auth/LoginView.jsx'])
    </head>
    <body>
        <div id="auth-root"></div>
    </body>
</html>
