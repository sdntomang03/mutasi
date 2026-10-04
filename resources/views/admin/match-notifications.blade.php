@extends('layouts.exchange')

@section('title', 'Email Kecocokan · Ruang Tukar Guru')

@section('content')
<div class="page-shell admin-shell">
    @include('partials.app-header')
    <main class="admin-main">
        <a class="back-link" href="{{ route('admin.users') }}">← Kembali ke daftar user</a>
        <section class="admin-intro">
            <p class="eyebrow">NOTIFIKASI</p>
            <h1>Email pemberitahuan kecocokan</h1>
            <p>Email dikirim langsung saat guru menyimpan profil. Bila gagal, alasannya dicatat di sini dan admin dapat mengirim ulang secara manual.</p>
        </section>

        @unless ($mailerIsReal)
            <div class="notice notice-warning" style="margin-bottom:16px">
                <p><strong>Email belum dikirim sungguhan.</strong> <code>MAIL_MAILER</code> saat ini <code>{{ $mailer }}</code> sehingga pesan hanya ditulis ke log. Atur SMTP di file <code>.env</code> (lihat <code>.env.example</code>), jalankan <code>php artisan config:clear</code>, lalu kirim ulang.</p>
            </div>
        @endunless
        @if ($pendingCount > 0)
            <div class="notice notice-warning" style="margin-bottom:16px">
                <p><strong>{{ $pendingCount }} pasangan belum berhasil diemail.</strong> Tekan "Kirim semua yang tertunda" setelah pengaturan email benar.</p>
            </div>
        @endif

        <section class="panel admin-users-panel">
            <div class="panel-heading">
                <div><p class="eyebrow">RIWAYAT</p><h2>Pasangan cocok</h2></div>
                <span class="step-number">{{ $pairs->total() }}</span>
            </div>
            <div class="deletion-request-actions" style="margin-bottom:16px">
                <button class="button button-primary" type="button" data-match-action data-url="{{ route('api.admin.match-notifications.send-pending') }}">Kirim semua yang tertunda</button>
                <button class="button button-secondary" type="button" data-match-action data-url="{{ route('api.admin.match-notifications.scan') }}">Pindai kecocokan baru</button>
            </div>
            <div class="admin-users-list">
                @forelse ($pairs as $pair)
                    @php($one = $profiles->get($pair->profile_one_id))
                    @php($two = $profiles->get($pair->profile_two_id))
                    <article class="admin-user-card">
                        <div class="admin-user-identity">
                            <h3>{{ $one?->name ?? 'Profil dihapus' }} ⇄ {{ $two?->name ?? 'Profil dihapus' }}</h3>
                            <p>{{ $one?->user?->email }} · {{ $two?->user?->email }}</p>
                            <span>Ditemukan {{ \Illuminate\Support\Carbon::parse($pair->created_at)->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="admin-user-meta">
                            <span><small>Status email</small>{{ $pair->sent_at ? 'Terkirim '.\Illuminate\Support\Carbon::parse($pair->sent_at)->format('d/m/Y H:i') : 'Belum terkirim' }}</span>
                            <span><small>Percobaan</small>{{ $pair->attempts }}</span>
                            @if ($pair->last_error)
                                <span><small>Alasan gagal</small>{{ $pair->last_error }}</span>
                            @endif
                            <div class="deletion-request-actions">
                                <button class="button button-ghost" type="button" data-match-action data-url="{{ route('api.admin.match-notifications.send', $pair->id) }}">{{ $pair->sent_at ? 'Kirim ulang' : 'Kirim email' }}</button>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="empty-state"><p>Belum ada pasangan cocok yang tercatat. Gunakan "Pindai kecocokan baru" untuk memeriksa profil yang sudah ada.</p></div>
                @endforelse
            </div>
            <div class="admin-pagination">{{ $pairs->links('vendor.pagination.admin-users') }}</div>
        </section>
    </main>
    <footer class="footer"><span>Ruang Tukar Guru · Panel administrator</span><span>Pengiriman email dicatat untuk setiap pasangan</span></footer>
</div>
<div class="toast" id="toast" role="status" aria-live="polite"></div>
<script src="{{ asset('admin-users.js').'?v='.filemtime(public_path('admin-users.js')) }}" defer></script>
@endsection
