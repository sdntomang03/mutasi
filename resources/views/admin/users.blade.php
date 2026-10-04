@extends('layouts.exchange')

@section('title', 'Daftar User · Ruang Tukar Guru')

@section('content')
<div class="page-shell admin-shell">
    @include('partials.app-header')
    <main class="admin-main">
        <a class="back-link" href="{{ route('admin.sudins') }}">← Pengaturan Sudin</a>
        <section class="admin-intro">
            <p class="eyebrow">PENGELOLAAN AKUN</p>
            <h1>Daftar user</h1>
            <p>Lihat akun aktif dan profil guru. Proses pengajuan penghapusan tersedia di menu terpisah.</p>
        </section>

        <section class="panel admin-users-panel">
            <div class="panel-heading"><div><p class="eyebrow">AKUN TERDAFTAR</p><h2>User terdaftar</h2></div><span class="step-number">{{ $users->count() }}</span></div>
            <div class="dt-toolbar">
                <label class="dt-length"><span>Tampilkan</span>
                    <select id="dt-length"><option>10</option><option>25</option><option>50</option><option>100</option></select>
                </label>
                <label class="dt-length"><span>Jabatan asal</span>
                    <select id="dt-position"><option value="">Semua</option><option value="guru_kelas">Guru kelas</option><option value="guru_mapel">Guru mapel</option></select>
                </label>
                <label class="dt-search"><span class="sr-only">Cari</span><input id="dt-search" type="search" placeholder="Cari nama, email, sekolah, sudin..."></label>
            </div>
            <div class="dt-scroll">
                <table class="dt-table" id="users-table">
                    <thead>
                        <tr>
                            <th data-sort="0">Nama</th>
                            <th data-sort="1">Asal</th>
                            <th data-sort="2">Tujuan</th>
                            <th data-sort="3">Status</th>
                            <th class="dt-nosort">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            @php
                                $profile = $user->teacherProfile;
                                $positions = \App\Models\TeacherProfile::POSITIONS;
                                $originSudin = $profile?->sudin?->abbreviation ?: $profile?->sudin?->name;
                                $destSudin = $profile?->destinationSudin?->abbreviation ?: $profile?->destinationSudin?->name;
                                $originPosition = $profile?->position ? $positions[$profile->position] : null;
                                $originSubject = $profile?->position === 'guru_mapel' ? $profile->subject?->name : null;
                                $destPosition = $profile?->destination_position ? $positions[$profile->destination_position] : null;
                                $destLevels = $profile?->destinationLevels->pluck('level')->join(', ');
                                $destSubjects = $profile?->destination_position === 'guru_mapel' ? $profile->destinationSubjects->pluck('name')->join(', ') : '';
                                $destDistricts = $profile?->destinationDistricts->pluck('name')->join(', ');
                                $statusText = ! $user->hasVerifiedEmail() ? 'Email belum terverifikasi' : ($profile ? ($profile->is_mutated ? 'Sudah mutasi' : 'Aktif') : 'Profil belum dibuat');
                            @endphp
                            <tr data-user-row data-position="{{ $profile?->position ?? '' }}">
                                <td data-order="{{ strtolower($profile?->name ?? $user->name) }}">
                                    <strong>{{ $profile?->name ?? $user->name }}</strong>
                                    <small>{{ $user->email }}</small>
                                </td>
                                <td>
                                    @if ($profile)
                                        <span class="dt-badge">{{ $originSudin }}</span>
                                        <span class="dt-badge dt-badge-soft">{{ $profile->employment_type }}</span>
                                        <small>{{ $originPosition ?? 'Jabatan belum diisi' }}{{ $profile->level ? ' · '.$profile->level : '' }}{{ $originSubject ? ' · '.$originSubject : '' }}</small>
                                    @else
                                        <small>Profil guru belum dibuat</small>
                                    @endif
                                </td>
                                <td>
                                    @if ($profile && $profile->destination_sudin_id)
                                        <span class="dt-badge">{{ $destSudin }}</span>
                                        <small>{{ $destPosition ?? 'Jabatan belum diisi' }}{{ $destLevels ? ' · '.$destLevels : '' }}{{ $destSubjects ? ' · '.$destSubjects : '' }}</small>
                                    @else
                                        <small>Belum dipilih</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="email-verification-status {{ $statusText === 'Aktif' || $statusText === 'Sudah mutasi' ? 'is-verified' : 'is-unverified' }}">{{ $statusText }}</span>
                                </td>
                                <td>
                                    <button class="button button-secondary dt-detail-button" type="button" data-open-user="user-modal-{{ $user->id }}">Detail</button>
                                    <template id="user-modal-{{ $user->id }}">
                                        <article class="admin-user-card" @if ($profile) data-admin-profile="{{ $profile->id }}" @endif>
                                            <div class="admin-user-identity">
                                                <h3>{{ $profile?->name ?? $user->name }}</h3>
                                                <p>{{ $user->email }}</p>
                                                <div class="admin-email-verification">
                                                    @if ($user->hasVerifiedEmail())
                                                        <span class="email-verification-status is-verified">Email terverifikasi</span>
                                                    @else
                                                        <span class="email-verification-status is-unverified">Email belum terverifikasi</span>
                                                        <button class="button button-secondary" type="button" data-verify-user-email="{{ route('api.admin.users.verify-email', $user) }}">Verifikasi email user</button>
                                                    @endif
                                                </div>
                                                <span>{{ $profile ? $profile->school_name.' · '.$profile->district_name : 'Profil guru belum dibuat' }}</span>
                                                @if ($profile)
                                                    <span>No. HP: {{ $profile->phone }}</span>
                                                @endif
                                                @unless (auth()->user()->is($user))
                                                    <button
                                                        class="button button-danger admin-user-delete"
                                                        type="button"
                                                        data-delete-user="{{ route('api.admin.users.destroy', $user) }}"
                                                        data-user-email="{{ $user->email }}"
                                                    >Hapus permanen</button>
                                                @endunless
                                            </div>
                                            @if ($profile)
                                                <div class="admin-user-meta">
                                                    <span><small>Sudin asal</small>{{ $profile->sudin?->abbreviation ? $profile->sudin->abbreviation.' · ' : '' }}{{ $profile->sudin?->name }}</span>
                                                    <span><small>Status kepegawaian</small>{{ $profile->employment_type }}</span>
                                                    <span><small>Jabatan · jenjang asal</small>{{ $originPosition ? $originPosition.' · '.$profile->level.($originSubject ? ' · '.$originSubject : '') : 'Belum diisi' }}</span>
                                                    <span><small>Sudin tujuan</small>{{ $profile->destinationSudin?->abbreviation ? $profile->destinationSudin->abbreviation.' · ' : '' }}{{ $profile->destinationSudin?->name ?? 'Belum dipilih' }}</span>
                                                    <span><small>Jabatan · jenjang tujuan</small>{{ $destPosition ? $destPosition.' · '.$destLevels.($destSubjects ? ' · '.$destSubjects : '') : 'Belum diisi' }}</span>
                                                    <span><small>Kecamatan tujuan</small>{{ $destDistricts ?: 'Semua kecamatan di Sudin tujuan' }}</span>
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
                                    </template>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="dt-empty" id="dt-empty" hidden>Tidak ada data yang cocok.</div>
            <div class="dt-footer">
                <p class="admin-pagination-summary" id="dt-info"></p>
                <div class="admin-pagination-links" id="dt-pages"></div>
            </div>
        </section>    </main>
    <footer class="footer"><span>Ruang Tukar Guru · Panel administrator</span><span>Pengajuan penghapusan ada di menu terpisah</span></footer>
</div>
<div class="user-modal" id="user-modal" role="dialog" aria-modal="true" aria-label="Detail user" hidden><div class="user-modal-box"><button type="button" class="user-modal-close" id="user-modal-close" aria-label="Tutup">×</button><div id="user-modal-body"></div></div></div>
<div class="toast" id="toast" role="status" aria-live="polite"></div>
<script src="{{ asset('admin-users.js').'?v='.filemtime(public_path('admin-users.js')) }}" defer></script>
@endsection
