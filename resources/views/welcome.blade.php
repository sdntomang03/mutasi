<!doctype html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description"
        content="Temukan calon tukeran guru DKI Jakarta berdasarkan kecocokan Sudin asal dan tujuan.">
    <meta name="theme-color" content="#059669">
    <title>Ruang Tukar Guru · DKI Jakarta</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <script>
        document.documentElement.classList.add('js');
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'] } } } };
    </script>

    <style>
        :focus-visible {
            outline: 2px solid #059669;
            outline-offset: 3px;
            border-radius: 0.5rem;
        }

        /* Header */
        #site-header {
            background: rgba(255, 255, 255, .6);
            border-bottom: 1px solid rgba(226, 232, 240, .4);
            transition: background-color .3s, box-shadow .3s, border-color .3s;
        }

        #site-header.is-scrolled {
            background: rgba(255, 255, 255, .88);
            border-bottom-color: rgba(203, 213, 225, .7);
            box-shadow: 0 6px 20px -12px rgba(15, 23, 42, .18);
        }

        /* Hero visual */
        .dot-grid {
            background-image: radial-gradient(#cbd5e1 1px, transparent 1px);
            background-size: 18px 18px;
            -webkit-mask-image: radial-gradient(ellipse at center, #000 20%, transparent 72%);
            mask-image: radial-gradient(ellipse at center, #000 20%, transparent 72%);
            opacity: .55;
        }

        .orb {
            animation: float-decoration 9s ease-in-out infinite;
        }

        .orb-b {
            animation-delay: -4s;
        }

        @keyframes float-decoration {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        #swap-stage {
            perspective: 1100px;
        }

        .region-card {
            transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
        }

        .region-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 18px 32px -16px rgba(5, 150, 105, .35);
            border-color: #6ee7b7;
        }

        .is-swapping .region-card {
            transition: none;
            will-change: transform;
        }

        .is-swapping #card-origin {
            animation: swap-origin var(--swap-ms, 1100ms) cubic-bezier(.65, 0, .35, 1) forwards;
            z-index: 20;
        }

        .is-swapping #card-dest {
            animation: swap-destination var(--swap-ms, 1100ms) cubic-bezier(.65, 0, .35, 1) forwards;
            z-index: 10;
        }

        @keyframes swap-origin {
            0% {
                transform: translate3d(0, 0, 0) rotateY(0) scale(1);
            }

            50% {
                transform: translate3d(-14px, calc(var(--dy) * .5), 40px) rotateY(-14deg) scale(1.03);
                box-shadow: 0 26px 40px -14px rgba(5, 150, 105, .32);
            }

            100% {
                transform: translate3d(0, var(--dy), 0) rotateY(0) scale(1);
            }
        }

        @keyframes swap-destination {
            0% {
                transform: translate3d(0, 0, 0) rotateY(0) scale(1);
            }

            50% {
                transform: translate3d(14px, calc(var(--dy) * -.5), -30px) rotateY(14deg) scale(.98);
                box-shadow: 0 20px 34px -14px rgba(8, 145, 178, .28);
            }

            100% {
                transform: translate3d(0, calc(var(--dy) * -1), 0) rotateY(0) scale(1);
            }
        }

        .swap-fade {
            transition: opacity .15s ease;
        }

        .swap-fade.is-fading {
            opacity: 0;
        }

        .flow-line {
            width: 2px;
            background-image: repeating-linear-gradient(to bottom, #10b981 0 6px, transparent 6px 12px);
            background-size: 2px 12px;
            background-repeat: repeat-y;
            animation: dash 1.4s linear infinite;
            filter: drop-shadow(0 0 4px rgba(16, 185, 129, .45));
        }

        .is-swapping .flow-line {
            animation-duration: .4s;
        }

        @keyframes dash {
            to {
                background-position: 0 12px;
            }
        }

        #swap-arrow {
            transition: transform var(--swap-ms, 1100ms) cubic-bezier(.65, 0, .35, 1);
        }

        .badge-pulse {
            animation: pulse-match .8s ease-out;
        }

        @keyframes pulse-match {
            0% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, .45);
            }

            100% {
                box-shadow: 0 0 0 12px rgba(16, 185, 129, 0);
            }
        }

        /* Scroll reveal (only when JS is available) */
        .js .reveal {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity .7s ease, transform .7s cubic-bezier(.22, .61, .36, 1);
        }

        .js .reveal.is-visible {
            opacity: 1;
            transform: none;
        }

        /* Partial: unofficial notice */
        .unofficial-notice {
            margin: 0;
            font-size: .75rem;
            line-height: 1.6;
            color: #94a3b8;
        }

        .unofficial-notice strong {
            color: #6ee7b7;
            font-weight: 700;
        }

        @media (prefers-reduced-motion: reduce) {
            html {
                scroll-behavior: auto;
            }

            .orb,
            .flow-line {
                animation: none;
            }

            .js .reveal {
                opacity: 1;
                transform: none;
                transition: none;
            }

            .badge-pulse {
                animation: none;
            }

            #swap-arrow,
            .region-card,
            #site-header {
                transition: none;
            }
        }
    </style>
</head>

<body class="min-h-screen overflow-x-hidden bg-white font-sans text-slate-900 antialiased">

    <header id="site-header" class="sticky top-0 z-50 backdrop-blur-xl">
        <div class="mx-auto flex h-[72px] max-w-6xl items-center justify-between gap-2 px-4 sm:px-6">
            <a href="{{ route('welcome') }}" aria-label="Ruang Tukar Guru, beranda"
                class="group flex items-center gap-2.5">
                <span
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-md shadow-emerald-600/25 ring-1 ring-white/60 transition-transform duration-300 group-hover:scale-[1.04] [&>svg]:h-6 [&>svg]:w-6">
                    @include('partials.brand-icon')
                </span>
                <span
                    class="hidden min-[380px]:block text-sm font-extrabold leading-tight tracking-tight text-slate-900">
                    Ruang Tukar Guru
                    <small class="block text-[11px] font-semibold text-emerald-700">DKI Jakarta</small>
                </span>
            </a>
            <nav class="flex items-center gap-1 sm:gap-2" aria-label="Navigasi utama">
                <a href="#cara-kerja"
                    class="hidden rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 transition-colors hover:text-emerald-700 sm:block">Cara
                    kerja</a>
                <a href="{{ route('login') }}"
                    class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-100">Masuk</a>
                <a href="{{ route('register') }}"
                    class="inline-flex items-center rounded-xl bg-emerald-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-600/25 transition-all duration-200 hover:-translate-y-0.5 hover:bg-emerald-700 hover:shadow-lg hover:shadow-emerald-600/30 active:translate-y-0 active:scale-[.98]">Daftar
                    akun</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="relative" aria-labelledby="judul-utama">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-[560px] bg-[radial-gradient(60%_60%_at_70%_0%,rgba(16,185,129,.10),transparent)]"
                aria-hidden="true"></div>
            <div
                class="relative mx-auto grid max-w-6xl items-center gap-12 px-4 pb-16 pt-10 sm:px-6 sm:pt-14 lg:grid-cols-2 lg:gap-16 lg:pb-24 lg:pt-20">

                <div class="reveal">
                    <p
                        class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-[11px] font-bold uppercase tracking-wider text-emerald-800">
                        <span class="relative flex h-2 w-2" aria-hidden="true">
                            <span
                                class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-500 opacity-60 motion-reduce:animate-none"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                        </span>
                        Ruang kolaborasi guru DKI Jakarta
                    </p>
                    <h1 id="judul-utama"
                        class="mt-5 text-4xl font-extrabold leading-[1.08] tracking-tight text-slate-900 sm:text-5xl lg:text-6xl">
                        Temukan rekan untuk
                        <span
                            class="bg-gradient-to-r from-emerald-600 to-teal-500 bg-clip-text text-transparent">tukeran
                            mutasi.</span>
                    </h1>
                    <p class="mt-5 max-w-xl text-base leading-relaxed text-slate-600 sm:text-lg">Cari guru dengan
                        rencana mutasi yang saling cocok. Mulai dari Sudin asal dan tujuan, lalu temukan kecocokan
                        dengan lebih mudah.</p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                        <a href="{{ route('register') }}"
                            class="group inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-6 py-3.5 text-base font-semibold text-white shadow-lg shadow-emerald-600/25 transition-all duration-200 hover:-translate-y-0.5 hover:bg-emerald-700 hover:shadow-xl hover:shadow-emerald-600/35 active:translate-y-0 active:scale-[.98] sm:w-auto">
                            Mulai cari tukeran
                            <svg class="h-4 w-4 transition-transform duration-200 group-hover:translate-x-0.5"
                                viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M4 10h12M11 5l5 5-5 5" />
                            </svg>
                        </a>
                        <a href="{{ route('login') }}"
                            class="inline-flex w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-6 py-3.5 text-base font-semibold text-slate-700 transition-all duration-200 hover:-translate-y-0.5 hover:border-emerald-300 hover:text-emerald-800 active:scale-[.98] sm:w-auto">Sudah
                            punya akun? Masuk</a>
                    </div>

                    <div
                        class="mt-8 flex max-w-md items-start gap-3 rounded-2xl border border-slate-200 bg-white/80 p-4 shadow-sm">
                        <span
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700"
                            aria-hidden="true">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 3l7 3v5c0 4.5-3 8-7 10-4-2-7-5.5-7-10V6l7-3z" />
                                <path d="M9 12l2 2 4-4" />
                            </svg>
                        </span>
                        <p class="text-sm leading-relaxed text-slate-600"><strong
                                class="block font-bold text-slate-900">Kontak terlindungi</strong>Kontak hanya dapat
                            diakses oleh calon yang memiliki kecocokan dua arah (2-way match).</p>
                    </div>
                </div>

                <div class="reveal" style="transition-delay:150ms">
                    <div class="relative mx-auto w-full max-w-md overflow-hidden rounded-3xl border border-slate-200 bg-[radial-gradient(circle_at_30%_0%,#ecfdf5,#fff_65%)] p-5 shadow-xl shadow-slate-900/5 sm:p-7"
                        role="group" aria-label="Ilustrasi pertukaran wilayah guru">
                        <div class="dot-grid pointer-events-none absolute inset-0" aria-hidden="true"></div>
                        <div class="orb pointer-events-none absolute -right-10 -top-10 h-40 w-40 rounded-full bg-emerald-300/30 blur-3xl"
                            aria-hidden="true"></div>
                        <div class="orb orb-b pointer-events-none absolute -bottom-12 -left-10 h-40 w-40 rounded-full bg-cyan-300/25 blur-3xl"
                            aria-hidden="true"></div>

                        <div class="relative">
                            <p id="match-badge"
                                class="mx-auto mb-5 flex w-fit items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                                    stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"
                                    aria-hidden="true">
                                    <path d="M4 10.5l4 4 8-9" />
                                </svg>
                                Kecocokan dua arah
                            </p>

                            <div id="swap-stage" class="flex flex-col" style="--dy:0px">
                                <div id="card-origin"
                                    class="region-card relative flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-md shadow-slate-900/5 sm:p-5">
                                    <span
                                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 transition-transform duration-200 [.region-card:hover_&]:scale-105"
                                        aria-hidden="true">
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21z" />
                                            <circle cx="12" cy="9.5" r="2.5" />
                                        </svg>
                                    </span>
                                    <span class="min-w-0">
                                        <span
                                            class="swap-fade block text-[11px] font-bold uppercase tracking-widest text-slate-500"
                                            data-label>Sudin asal</span>
                                        <strong class="block truncate text-xl font-bold text-slate-900 sm:text-2xl"
                                            data-name>Jakarta Barat</strong>
                                        <span class="swap-fade block text-xs text-slate-500" data-hint>Wilayah saat
                                            ini</span>
                                    </span>
                                </div>

                                <div class="relative h-20" aria-hidden="false">
                                    <span class="flow-line absolute left-1/2 top-0 h-[18px] -translate-x-1/2"
                                        aria-hidden="true"></span>
                                    <span class="flow-line absolute bottom-0 left-1/2 h-[18px] -translate-x-1/2"
                                        aria-hidden="true"></span>
                                    <div class="group absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                                        <span
                                            class="pointer-events-none absolute inset-0 rounded-full bg-emerald-400/30 animate-ping motion-reduce:hidden"
                                            style="animation-duration:2.6s" aria-hidden="true"></span>
                                        <button id="swap-btn" type="button"
                                            aria-label="Simulasikan pertukaran Sudin asal dan Sudin tujuan"
                                            class="relative z-30 flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-lg shadow-emerald-600/30 ring-4 ring-white transition-transform duration-200 hover:scale-110 active:scale-95">
                                            <svg id="swap-arrow" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2.2" stroke-linecap="round"
                                                stroke-linejoin="round" aria-hidden="true">
                                                <path d="M7 4v14M3 14l4 4 4-4" />
                                                <path d="M17 20V6M13 10l4-4 4 4" />
                                            </svg>
                                        </button>
                                        <span role="tooltip"
                                            class="pointer-events-none absolute bottom-full left-1/2 z-40 mb-2 -translate-x-1/2 whitespace-nowrap rounded-lg bg-slate-900 px-2.5 py-1.5 text-xs font-medium text-white opacity-0 shadow-lg transition-opacity duration-150 group-hover:opacity-100 group-focus-within:opacity-100">Simulasikan
                                            pertukaran</span>
                                    </div>
                                </div>

                                <div id="card-dest"
                                    class="region-card relative flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-md shadow-slate-900/5 sm:p-5">
                                    <span
                                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 transition-transform duration-200 [.region-card:hover_&]:scale-105"
                                        aria-hidden="true">
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21z" />
                                            <circle cx="12" cy="9.5" r="2.5" />
                                        </svg>
                                    </span>
                                    <span class="min-w-0">
                                        <span
                                            class="swap-fade block text-[11px] font-bold uppercase tracking-widest text-slate-500"
                                            data-label>Sudin tujuan</span>
                                        <strong class="block truncate text-xl font-bold text-slate-900 sm:text-2xl"
                                            data-name>Jakarta Timur</strong>
                                        <span class="swap-fade block text-xs text-slate-500" data-hint>Wilayah yang
                                            dituju</span>
                                    </span>
                                </div>
                            </div>

                            <p class="mt-5 text-center text-xs text-slate-500">Ilustrasi simulasi. Ketuk tombol di
                                tengah untuk menukar.</p>
                            <p id="swap-live" class="sr-only" aria-live="polite"></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="cara-kerja" class="scroll-mt-24 border-t border-slate-200 bg-slate-50/70"
            aria-labelledby="judul-cara-kerja">
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:py-24">
                <div class="reveal max-w-2xl">
                    <p class="text-xs font-bold uppercase tracking-widest text-emerald-700">Dibuat untuk guru</p>
                    <h2 id="judul-cara-kerja"
                        class="mt-3 text-3xl font-extrabold leading-tight tracking-tight text-slate-900 sm:text-4xl">
                        Rencana mutasi yang saling menemukan.</h2>
                </div>

                <ol class="mt-12 grid gap-0 md:grid-cols-3 md:gap-6">
                    <li class="reveal group flex gap-4 md:block" style="transition-delay:0ms">
                        <div class="flex flex-col items-center md:mb-5 md:flex-row">
                            <span
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-slate-300 bg-white text-sm font-extrabold text-slate-500 transition-colors duration-300 group-hover:border-emerald-600 group-hover:bg-emerald-600 group-hover:text-white">01</span>
                            <span class="mt-2 w-px flex-1 bg-slate-300 md:ml-3 md:mt-0 md:h-px md:w-auto"
                                aria-hidden="true"></span>
                        </div>
                        <article
                            class="relative flex-1 rounded-2xl border border-slate-200 bg-white p-6 transition-all duration-300 group-hover:-translate-y-1 group-hover:border-emerald-300 group-hover:shadow-lg group-hover:shadow-emerald-900/5 max-md:mb-6">
                            <svg class="absolute right-5 top-5 h-6 w-6 text-slate-300 transition-all duration-300 group-hover:-translate-y-0.5 group-hover:text-emerald-500"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="8" r="3.5" />
                                <path d="M5 20c.8-3.6 3.7-5.5 7-5.5s6.2 1.9 7 5.5" />
                            </svg>
                            <h3 class="pr-8 text-lg font-bold text-slate-900">Lengkapi profil</h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">Tambahkan informasi sekolah, Sudin
                                asal, dan wilayah yang dituju.</p>
                        </article>
                    </li>
                    <li class="reveal group flex gap-4 md:block" style="transition-delay:100ms">
                        <div class="flex flex-col items-center md:mb-5 md:flex-row">
                            <span
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-slate-300 bg-white text-sm font-extrabold text-slate-500 transition-colors duration-300 group-hover:border-emerald-600 group-hover:bg-emerald-600 group-hover:text-white">02</span>
                            <span class="mt-2 w-px flex-1 bg-slate-300 md:ml-3 md:mt-0 md:h-px md:w-auto"
                                aria-hidden="true"></span>
                        </div>
                        <article
                            class="relative flex-1 rounded-2xl border border-slate-200 bg-white p-6 transition-all duration-300 group-hover:-translate-y-1 group-hover:border-emerald-300 group-hover:shadow-lg group-hover:shadow-emerald-900/5 max-md:mb-6">
                            <svg class="absolute right-5 top-5 h-6 w-6 text-slate-300 transition-all duration-300 group-hover:-translate-y-0.5 group-hover:text-emerald-500"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="11" cy="11" r="6.5" />
                                <path d="M16 16l4.5 4.5" />
                                <path d="M8.5 11l1.7 1.7 3-3.4" />
                            </svg>
                            <h3 class="pr-8 text-lg font-bold text-slate-900">Temukan kecocokan</h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">Hasil muncul ketika Sudin asal dan
                                tujuan kedua guru saling berlawanan.</p>
                        </article>
                    </li>
                    <li class="reveal group flex gap-4 md:block" style="transition-delay:200ms">
                        <div class="flex flex-col items-center md:mb-5 md:flex-row">
                            <span
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-slate-300 bg-white text-sm font-extrabold text-slate-500 transition-colors duration-300 group-hover:border-emerald-600 group-hover:bg-emerald-600 group-hover:text-white">03</span>
                        </div>
                        <article
                            class="relative flex-1 rounded-2xl border border-slate-200 bg-white p-6 transition-all duration-300 group-hover:-translate-y-1 group-hover:border-emerald-300 group-hover:shadow-lg group-hover:shadow-emerald-900/5">
                            <svg class="absolute right-5 top-5 h-6 w-6 text-slate-300 transition-all duration-300 group-hover:-translate-y-0.5 group-hover:text-emerald-500"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="4" y="10.5" width="16" height="10" rx="2.5" />
                                <path d="M8 10.5V8a4 4 0 0 1 8 0v2.5" />
                            </svg>
                            <h3 class="pr-8 text-lg font-bold text-slate-900">Hubungi dengan aman</h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">Detail kontak calon tukeran hanya
                                tersedia setelah kecocokan ditemukan.</p>
                        </article>
                    </li>
                </ol>
            </div>
        </section>
    </main>

    <footer class="bg-slate-900 text-slate-300">
        <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-extrabold text-white">Ruang Tukar Guru <span class="text-emerald-400">DKI
                            Jakarta</span></p>
                    <p class="mt-1 text-sm text-slate-400">Berbagi informasi, membuka kemungkinan.</p>
                </div>
                <nav class="flex gap-5 text-sm font-semibold" aria-label="Navigasi footer">
                    <a href="{{ route('login') }}"
                        class="text-slate-300 transition-colors hover:text-emerald-400">Masuk</a>
                    <a href="{{ route('register') }}"
                        class="text-slate-300 transition-colors hover:text-emerald-400">Daftar akun</a>
                </nav>
            </div>
            <div class="mt-8 border-t border-slate-700/70 pt-6">
                @include('partials.unofficial-notice')
            </div>
        </div>
    </footer>

    <script>
        (function () {
        'use strict';
        var reduce = window.matchMedia('(prefers-reduced-motion: reduce)');

        /* Header: berubah saat di-scroll */
        var header = document.getElementById('site-header');
        function onScroll() { header.classList.toggle('is-scrolled', window.scrollY > 8); }
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();

        /* Scroll reveal */
        var revealEls = document.querySelectorAll('.reveal');
        if ('IntersectionObserver' in window && !reduce.matches) {
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (e) {
                    if (e.isIntersecting) { e.target.classList.add('is-visible'); io.unobserve(e.target); }
                });
            }, { threshold: 0.12 });
            revealEls.forEach(function (el) { io.observe(el); });
        } else {
            revealEls.forEach(function (el) { el.classList.add('is-visible'); });
        }

        /* Swap engine (demo visual saja, tanpa data backend) */
        var stage = document.getElementById('swap-stage');
        if (!stage) return;

        var REGIONS = ['Jakarta Barat', 'Jakarta Timur'];
        var DURATION = 1100, INTERVAL = 4000;
        var cardOrigin = document.getElementById('card-origin');
        var cardDest = document.getElementById('card-dest');
        var btn = document.getElementById('swap-btn');
        var arrow = document.getElementById('swap-arrow');
        var badge = document.getElementById('match-badge');
        var live = document.getElementById('swap-live');
        var cards = [cardOrigin, cardDest];
        var names = cards.map(function (c) { return c.querySelector('[data-name]'); });
        var labels = cards.map(function (c) { return c.querySelector('[data-label]'); });
        var hints = cards.map(function (c) { return c.querySelector('[data-hint]'); });
        var ROLE = [
            { label: 'Sudin asal', hint: 'Wilayah saat ini' },
            { label: 'Sudin tujuan', hint: 'Wilayah yang dituju' }
        ];

        var isSwapped = false;
        var isAnimating = false;
        var inView = true;
        var rotation = 0;
        var autoTimer = null;
        var timers = [];

        stage.style.setProperty('--swap-ms', DURATION + 'ms');
        arrow.style.setProperty('--swap-ms', DURATION + 'ms');

        function later(fn, ms) { timers.push(setTimeout(fn, ms)); }
        function clearTimers() { timers.forEach(clearTimeout); timers = []; }

        function setRoles(flipped) {
            [0, 1].forEach(function (i) {
                var role = ROLE[flipped ? 1 - i : i];
                labels[i].textContent = role.label;
                hints[i].textContent = role.hint;
            });
        }
        function setFading(on) {
            labels.concat(hints).forEach(function (el) { el.classList.toggle('is-fading', on); });
        }
        function render() {
            names[0].textContent = REGIONS[isSwapped ? 1 : 0];
            names[1].textContent = REGIONS[isSwapped ? 0 : 1];
            setRoles(false);
        }

        function startAutoSwap() {
            if (autoTimer || reduce.matches || document.hidden || !inView || isAnimating) return;
            autoTimer = setTimeout(function () { autoTimer = null; swapLocations(false); }, INTERVAL);
        }
        function stopAutoSwap() { clearTimeout(autoTimer); autoTimer = null; }
        function restartAutoSwap() { stopAutoSwap(); startAutoSwap(); }

        function finish(manual) {
            isAnimating = false;
            badge.classList.remove('badge-pulse');
            void badge.offsetWidth;
            if (!reduce.matches) badge.classList.add('badge-pulse');
            if (manual) {
                live.textContent = 'Sudin asal: ' + names[0].textContent + '. Sudin tujuan: ' + names[1].textContent + '.';
            }
            restartAutoSwap();
        }

        function swapLocations(manual) {
            if (isAnimating) return;
            isAnimating = true;
            stopAutoSwap();
            isSwapped = !isSwapped;
            rotation += 180;
            arrow.style.transform = 'rotate(' + rotation + 'deg)';

            if (reduce.matches) { render(); finish(manual); return; }

            var dy = cardDest.getBoundingClientRect().top - cardOrigin.getBoundingClientRect().top;
            stage.style.setProperty('--dy', dy + 'px');
            stage.classList.add('is-swapping');

            later(function () { setFading(true); }, DURATION * 0.40);
            later(function () { setRoles(true); }, DURATION * 0.50);
            later(function () { setFading(false); }, DURATION * 0.55);
            later(function () {
                stage.classList.remove('is-swapping');
                render();
                finish(manual);
            }, DURATION + 30);
        }

        btn.addEventListener('click', function () { swapLocations(true); });

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) stopAutoSwap(); else startAutoSwap();
        });
        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (entries) {
                inView = entries[0].isIntersecting;
                if (inView) startAutoSwap(); else stopAutoSwap();
            }, { threshold: 0.2 }).observe(stage);
        }
        if (reduce.addEventListener) {
            reduce.addEventListener('change', function () {
                if (reduce.matches) { stopAutoSwap(); } else { startAutoSwap(); }
            });
        }
        window.addEventListener('pagehide', function () { stopAutoSwap(); clearTimers(); });

        startAutoSwap();
    })();
    </script>
</body>

</html>