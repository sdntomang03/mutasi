@php($authUser = auth()->user())
@php($isAdmin = $authUser->hasRole('admin'))
@php($links = $isAdmin ? [
    ['admin.sudins', 'Pengaturan Sudin'],
    ['admin.subjects', 'Mapel'],
    ['admin.users', 'Daftar User'],
    ['admin.deletion-requests', 'Pengajuan Hapus'],
    ['admin.match-notifications', 'Email Kecocokan'],
] : [
    ['dashboard', 'Dashboard'],
    ['teacher-directory', 'Daftar Guru'],
    ['teacher-profile.edit', 'Profil'],
    ['guide', 'Panduan'],
])
<header class="topbar app-topbar">
    <a class="brand" href="{{ $isAdmin ? route('admin.users') : route('dashboard') }}">
        <span class="brand-mark">@include('partials.brand-icon')</span>
        <span>Ruang Tukar Guru <small>DKI Jakarta</small></span>
    </a>
    <nav class="app-navigation" aria-label="Navigasi utama">
        @foreach ($links as [$routeName, $label])
            <a href="{{ route($routeName) }}" @class(['nav-link', 'nav-link-active' => request()->routeIs($routeName)]) @if (request()->routeIs($routeName)) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>
    <details class="account-menu">
        <summary aria-label="Menu akun">
            <span class="account-avatar">{{ mb_strtoupper(mb_substr($authUser->name, 0, 1)) }}</span>
            <span class="account-info"><strong>{{ $authUser->name }}</strong><small>{{ $isAdmin ? 'Administrator' : 'Guru' }}</small></span>
            <svg class="account-caret" viewBox="0 0 20 20" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 8 5 5 5-5"/></svg>
        </summary>
        <div class="account-dropdown">
            <div class="account-dropdown-head"><strong>{{ $authUser->name }}</strong><small>{{ $authUser->email }}</small></div>
            <a href="{{ route('account.password') }}" @class(['account-item', 'account-item-active' => request()->routeIs('account.password')])>
                <svg viewBox="0 0 20 20" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="9" width="12" height="8" rx="2"/><path d="M7 9V6.5a3 3 0 0 1 6 0V9"/></svg>
                Ganti password
            </a>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="account-item account-item-danger" type="submit">
                    <svg viewBox="0 0 20 20" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 4H5.5A1.5 1.5 0 0 0 4 5.5v9A1.5 1.5 0 0 0 5.5 16H8M12 6l4 4-4 4M16 10H8"/></svg>
                    Keluar
                </button>
            </form>
        </div>
    </details>
</header>

<script>
    (() => {
        const menu = document.querySelector('.account-menu');
        if (!menu) { return; }
        document.addEventListener('click', (event) => {
            if (menu.open && !menu.contains(event.target)) { menu.open = false; }
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') { menu.open = false; }
        });
    })();
</script>