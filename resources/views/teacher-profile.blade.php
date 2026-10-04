@extends('layouts.exchange')

@section('title', 'Profil Mutasi · Ruang Tukar Guru DKI')

@section('content')
<div class="page-shell">
    @include('partials.app-header')
    <main class="profile-main">
        <a class="back-link" href="{{ route('dashboard') }}">← Kembali ke dashboard</a>
        <section class="dashboard-heading profile-heading">
            <div>
                <p class="eyebrow">PROFIL GURU</p>
                <h1>Profil mutasi</h1>
                <p>Perbarui informasi sekolah asal dan wilayah tujuan mutasimu.</p>
            </div>
        </section>
        <section class="panel form-panel profile-form-panel">
            @include('partials.teacher-profile-form')
        </section>
        @if (auth()->user()->teacherProfile)
        @php($pendingDeletion = auth()->user()->teacherProfile->deletionRequests()->where('status', 'pending')->exists())
        <section class="panel mutation-status-panel" id="profile-status"
            data-is-mutated="{{ auth()->user()->teacherProfile->is_mutated ? '1' : '0' }}"
            data-pending-deletion="{{ $pendingDeletion ? '1' : '0' }}">
            <div>
                <p class="eyebrow">STATUS MUTASI</p>
                <h2 id="mutation-status-label">{{ auth()->user()->teacherProfile->is_mutated ? 'Sudah mutasi' : 'Belum mutasi' }}</h2>
                <p class="muted-text">Tandai status ini agar admin dan calon tukeran mengetahui apakah profil masih aktif.</p>
            </div>
            <div class="mutation-status-actions">
                <select id="mutation-status-select" aria-label="Status mutasi">
                    <option value="0" @selected(! auth()->user()->teacherProfile->is_mutated)>Belum mutasi</option>
                    <option value="1" @selected(auth()->user()->teacherProfile->is_mutated)>Sudah mutasi</option>
                </select>
                <button class="button button-secondary" id="save-mutation-status" type="button">Simpan status</button>
                <button class="button button-ghost" id="request-profile-deletion" type="button" @disabled(! auth()->user()->teacherProfile->is_mutated || $pendingDeletion)>
                    {{ $pendingDeletion ? 'Pengajuan menunggu admin' : 'Ajukan penghapusan profil' }}
                </button>
            </div>
        </section>
        @else
            <p class="notice notice-info">Simpan profil terlebih dahulu sebelum menandai status mutasi atau mengajukan penghapusan.</p>
        @endif
    </main>
    <footer class="footer"><span>Ruang Tukar Guru · DKI Jakarta</span><span>Data profil hanya dibagikan kepada calon yang cocok</span></footer>
</div>
<div class="toast" id="toast" role="status" aria-live="polite"></div>
<script src="{{ asset('app.js') }}" defer></script>
@endsection
