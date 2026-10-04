<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Ruang Tukar Guru DKI')</title>
    <link rel="stylesheet" href="{{ asset('app.css').'?v='.filemtime(public_path('app.css')) }}">
</head>
<body>
    @yield('content')
    @include('partials.unofficial-notice')
    @stack('scripts')
</body>
</html>
