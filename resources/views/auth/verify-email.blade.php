<x-guest-layout>
    <div class="auth-heading">
        <p class="eyebrow">SATU LANGKAH LAGI</p>
        <h2>Verifikasi emailmu</h2>
        <p>Terima kasih sudah mendaftar. Klik tautan verifikasi yang kami kirim melalui email untuk mulai menggunakan Ruang Tukar Guru.</p>
    </div>
    @if (session('status') == 'verification-link-sent')
        <div class="notice notice-info auth-status"><span class="notice-icon" aria-hidden="true">i</span><p>Tautan verifikasi baru sudah dikirim ke alamat email yang kamu daftarkan.</p></div>
    @endif
    <div class="auth-form">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button class="button button-primary auth-submit">Kirim ulang email verifikasi</x-primary-button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="auth-logout">Keluar</button>
        </form>
    </div>
</x-guest-layout>
