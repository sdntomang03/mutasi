@extends('layouts.exchange')

@section('title', 'Daftar User · Ruang Tukar Guru')

@section('content')
<div class="page-shell admin-shell">
    @include('partials.app-header')
    <main class="admin-main">
        <a class="back-link" href="{{ route('dashboard') }}">← Kembali ke dashboard</a>
        <section class="admin-intro">
            <p class="eyebrow">PENGELOLAAN AKUN</p>
            <h1>Daftar user</h1>
            <p>Lihat akun aktif dan profil guru. Proses pengajuan penghapusan tersedia di menu terpisah.</p>
        </section>

        <section class="panel admin-users-panel">
            <div class="panel-heading"><div><p class="eyebrow">AKUN TERDAFTAR</p><h2>User terdaftar</h2></div><span class="step-number">{{ $users->total() }}</span></div>
            <div class="admin-users-list">
                @forelse ($users as $user)
                    @php($profile = $user->teacherProfile)
                    <article class="admin-user-card" @if ($profile) data-admin-profile="{{ $profile->id }}" @endif>
                        <div class="admin-user-identity">
                            <h3>{{ $profile?->name ?? $user->name }}</h3>
                            <p>{{ $user->email }}</p>
                            <span>{{ $profile ? $profile->school_name.' · '.$profile->district_name : 'Profil guru belum dibuat' }}</span>
                        </div>
                        @if ($profile)
                        <div class="admin-user-meta">
                            <span><small>Sudin asal</small>{{ $profile->sudin->name }}</span>
                            <span><small>Sudin tujuan</small>{{ $profile->destinationSudin?->name ?? 'Belum dipilih' }}</span>
                            <label class="field"><span>Status mutasi</span>
                                <select data-admin-mutation-status>
                                    <option value="0" @selected(! $profile->is_mutated)>Belum mutasi</option>
                                    <option value="1" @selected($profile->is_mutated)>Sudah mutasi</option>
                                </select>
                            </label>
                            <button class="button button-secondary" type="button" data-save-admin-status="{{ route('api.admin.teachers.status', $profile) }}">Simpan status</button>
                        </div>
                        @endif
                    </article>
                @empty
                    <div class="empty-state"><span class="empty-symbol">⌁</span><p>Belum ada user terdaftar.</p></div>
                @endforelse
            </div>
            <div class="admin-pagination">{{ $users->links('vendor.pagination.admin-users') }}</div>
        </section>
    </main>
    <footer class="footer"><span>Ruang Tukar Guru · Panel administrator</span><span>Pengajuan penghapusan ada di menu terpisah</span></footer>
</div>
<div class="toast" id="toast" role="status" aria-live="polite"></div>
<script src="{{ asset('admin-users.js') }}" defer></script>
@endsection
