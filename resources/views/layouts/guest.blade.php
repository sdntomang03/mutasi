<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ match (true) {
        request()->routeIs('login') => 'Masuk',
        request()->routeIs('register') => 'Daftar akun',
        request()->routeIs('password.request') => 'Lupa kata sandi',
        request()->routeIs('password.reset') => 'Reset kata sandi',
        request()->routeIs('password.confirm') => 'Konfirmasi kata sandi',
        request()->routeIs('verification.notice') => 'Verifikasi email',
        default => 'Akun',
    } }} · Ruang Tukar Guru · DKI Jakarta</title>
    <link rel="stylesheet" href="{{ asset('app.css').'?v='.filemtime(public_path('app.css')) }}">
</head>
<body class="auth-body">
    <main class="auth-layout">
        <aside class="auth-aside">
            <a class="brand auth-brand" href="{{ route('welcome') }}">
                <span class="brand-mark">@include('partials.brand-icon')</span>
                <span>Ruang Tukar Guru <small>DKI Jakarta</small></span>
            </a>
            <div class="auth-aside-content">
                <p class="eyebrow">RUANG KOLABORASI GURU</p>
                <h1>Temukan jalan<br>mutasi <em>bersama.</em></h1>
                <p>Hubungkan rencana mutasimu dengan guru lain melalui kecocokan Sudin asal dan tujuan di DKI Jakarta.</p>
                <div class="auth-aside-note">
                    <span aria-hidden="true">↔</span>
                    <span>Lebih mudah menemukan calon tukeran yang tepat.</span>
                </div>
            </div>
            <span class="auth-aside-footer">Ruang Tukar Guru · DKI Jakarta</span>
        </aside>
        <section class="auth-main">
            <div class="auth-mobile-brand">
                <a class="brand" href="{{ route('welcome') }}">
                    <span class="brand-mark">@include('partials.brand-icon')</span>
                    <span>Ruang Tukar Guru <small>DKI Jakarta</small></span>
                </a>
            </div>
            <div class="auth-card">
                {{ $slot }}
            </div>
            @include('partials.unofficial-notice')
            <p class="auth-footer"><a href="{{ route('welcome') }}">← Kembali ke beranda</a></p>
        </section>
    </main>
</body>
</html>
