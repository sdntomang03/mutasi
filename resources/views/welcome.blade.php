<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Temukan calon tukeran guru DKI Jakarta berdasarkan kecocokan Sudin asal dan tujuan.">
    <title>Ruang Tukar Guru · DKI Jakarta</title>
    <link rel="stylesheet" href="{{ asset('app.css').'?v='.filemtime(public_path('app.css')) }}">
</head>
<body class="welcome-body">
    <div class="welcome-shell">
        <header class="welcome-header">
            <a class="brand" href="{{ route('welcome') }}" aria-label="Ruang Tukar Guru, beranda">
                <span class="brand-mark">RT</span>
                <span>Ruang Tukar Guru <small>DKI Jakarta</small></span>
            </a>
            <nav class="welcome-nav" aria-label="Navigasi utama">
                <a href="#cara-kerja">Cara kerja</a>
                <a class="button button-ghost" href="{{ route('login') }}">Masuk</a>
                <a class="button button-primary" href="{{ route('register') }}">Daftar akun <span aria-hidden="true">→</span></a>
            </nav>
        </header>

        <main>
            <section class="welcome-hero">
                <div class="welcome-copy">
                    <p class="eyebrow"><span class="welcome-live-dot"></span> RUANG KOLABORASI GURU DKI JAKARTA</p>
                    <h1>Temukan rekan<br>untuk <em>tukeran.</em></h1>
                    <p class="welcome-lead">Cari guru dengan rencana mutasi yang saling cocok. Mulai dari Sudin asal dan tujuan, lalu temukan kecocokan dengan lebih mudah.</p>
                    <div class="welcome-actions">
                        <a class="button button-primary" href="{{ route('register') }}">Mulai cari tukeran <span aria-hidden="true">→</span></a>
                        <a class="welcome-secondary-link" href="{{ route('login') }}">Sudah punya akun? Masuk</a>
                    </div>
                    <div class="welcome-assurance">
                        <span class="assurance-icon" aria-hidden="true">✓</span>
                        <span>Kontak hanya terlihat oleh calon dengan kecocokan dua arah.</span>
                    </div>
                </div>
                <div class="welcome-visual" aria-label="Ilustrasi pertukaran wilayah guru">
                    <div class="visual-orbit visual-orbit-one"></div>
                    <div class="visual-orbit visual-orbit-two"></div>
                    <div class="visual-route"><span></span><i></i><span></span></div>
                    <div class="region-card region-card-west"><span class="region-pin">W</span><span><small>ASAL</small><strong>Jakarta Barat</strong></span></div>
                    <div class="region-card region-card-east"><span class="region-pin region-pin-gold">T</span><span><small>TUJUAN</small><strong>Jakarta Timur</strong></span></div>
                    <div class="visual-center-mark">↔</div>
                    <span class="visual-caption">SATU LANGKAH MENUJU KECOCOKAN</span>
                </div>
            </section>

            <section class="welcome-benefits" id="cara-kerja">
                <div class="benefits-heading">
                    <p class="eyebrow">DIBUAT UNTUK GURU</p>
                    <h2>Rencana mutasi yang saling menemukan.</h2>
                </div>
                <div class="benefit-grid">
                    <article class="benefit-card">
                        <span class="benefit-number">01</span>
                        <div><h3>Lengkapi profil</h3><p>Tambahkan informasi sekolah, Sudin asal, dan wilayah yang dituju.</p></div>
                    </article>
                    <article class="benefit-card">
                        <span class="benefit-number">02</span>
                        <div><h3>Temukan kecocokan</h3><p>Hasil muncul ketika Sudin asal dan tujuan kedua guru saling berlawanan.</p></div>
                    </article>
                    <article class="benefit-card">
                        <span class="benefit-number">03</span>
                        <div><h3>Hubungi dengan aman</h3><p>Detail kontak calon tukeran hanya tersedia setelah kecocokan ditemukan.</p></div>
                    </article>
                </div>
            </section>
        </main>

        <footer class="welcome-footer">
            <span>Ruang Tukar Guru · DKI Jakarta</span>
            <span>Berbagi informasi, membuka kemungkinan.</span>
        </footer>
    </div>
</body>
</html>
