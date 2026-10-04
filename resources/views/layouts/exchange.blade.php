<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Ruang Tukar Guru DKI')</title>
    <link rel="stylesheet" href="{{ asset('app.css') }}">
</head>
<body>
    @yield('content')
    @stack('scripts')
</body>
</html>
