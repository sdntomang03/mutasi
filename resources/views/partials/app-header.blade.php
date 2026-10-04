<header class="topbar">
    <a class="brand" href="{{ auth()->user()->hasRole('admin') ? route('admin.users') : route('dashboard') }}">
        <span class="brand-mark">RT</span>
        <span>Ruang Tukar Guru <small>DKI Jakarta</small></span>
    </a>
    <nav class="app-navigation" aria-label="Navigasi utama">
        @if (auth()->user()->hasRole('admin'))
            <a href="{{ route('admin.sudins') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('admin.sudins')])>Pengaturan Sudin</a>
            <a href="{{ route('admin.subjects') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('admin.subjects')])>Mapel</a>
            <a href="{{ route('admin.users') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('admin.users')])>Daftar User</a>
            <a href="{{ route('admin.deletion-requests') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('admin.deletion-requests')])>Pengajuan Hapus</a>
            <a href="{{ route('admin.match-notifications') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('admin.match-notifications')])>Email Kecocokan</a>
        @else
            <a href="{{ route('dashboard') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('dashboard')])>Dashboard</a>
            <a href="{{ route('teacher-profile.edit') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('teacher-profile.edit')])>Profil</a>
            <a href="{{ route('guide') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('guide')])>Panduan</a>
        @endif
    </nav>
    <div class="account-actions">
        <span class="account-name">{{ auth()->user()->name }}</span>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="quiet-link button-link" type="submit">Keluar</button></form>
    </div>
</header>
