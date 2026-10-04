@extends('layouts.exchange')

@section('title', 'Pengajuan Hapus · Ruang Tukar Guru')

@section('content')
<div class="page-shell admin-shell">
    @include('partials.app-header')
    <main class="admin-main">
        <a class="back-link" href="{{ route('admin.users') }}">← Kembali ke daftar user</a>
        <section class="admin-intro">
            <p class="eyebrow">TINJAUAN ADMIN</p>
            <h1>Pengajuan penghapusan</h1>
            <p>Persetujuan akan menonaktifkan login dan mengarsipkan akun serta profil (soft delete). Data tetap tersimpan di database dan tidak tampil di pencarian maupun daftar user aktif.</p>
        </section>

        <section class="panel admin-users-panel">
            <div class="panel-heading"><div><p class="eyebrow">MENUNGGU KEPUTUSAN</p><h2>Pengajuan aktif</h2></div><span class="step-number">{{ $deletionRequests->total() }}</span></div>
            <div class="admin-users-list">
                @forelse ($deletionRequests as $deletionRequest)
                    <article class="admin-user-card">
                        <div class="admin-user-identity">
                            <h3>{{ $deletionRequest->requester_name }}</h3>
                            <p>{{ $deletionRequest->requester_email }}</p>
                            <span>Diajukan {{ $deletionRequest->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="admin-user-meta">
                            @if ($deletionRequest->profile)
                                <span><small>Nama profil</small>{{ $deletionRequest->profile->name }}</span>
                                <span><small>Sekolah</small>{{ $deletionRequest->profile->school_name }}</span>
                                <span><small>Status mutasi</small>{{ $deletionRequest->profile->is_mutated ? 'Sudah mutasi' : 'Belum mutasi' }}</span>
                            @else
                                <span>Profil tidak lagi tersedia.</span>
                            @endif
                            <div class="deletion-request-actions">
                                <button class="button button-primary" type="button" data-review-deletion="approve" data-url="{{ route('api.admin.profile-deletion-requests.review', $deletionRequest) }}">Setujui & nonaktifkan akun</button>
                                <button class="button button-ghost" type="button" data-review-deletion="reject" data-url="{{ route('api.admin.profile-deletion-requests.review', $deletionRequest) }}">Tolak</button>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="empty-state"><span class="empty-symbol">⌁</span><p>Tidak ada pengajuan penghapusan yang menunggu keputusan.</p></div>
                @endforelse
            </div>
            <div class="admin-pagination">{{ $deletionRequests->links('vendor.pagination.admin-users') }}</div>
        </section>
    </main>
    <footer class="footer"><span>Ruang Tukar Guru · Panel administrator</span><span>Soft delete menjaga data tetap tersimpan</span></footer>
</div>
<div class="toast" id="toast" role="status" aria-live="polite"></div>
<script src="{{ asset('admin-users.js').'?v='.filemtime(public_path('admin-users.js')) }}" defer></script>
@endsection
