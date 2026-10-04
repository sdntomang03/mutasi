<x-guest-layout>
    <div class="auth-heading">
        <p class="eyebrow">BANTUAN AKUN</p>
        <h2>Lupa kata sandi?</h2>
        <p>Masukkan email yang terdaftar. Kami akan mengirimkan tautan untuk membuat kata sandi baru.</p>
    </div>
    <x-auth-session-status class="auth-status" :status="session('status')" />
    <form method="POST" action="{{ route('password.email') }}" class="auth-form">
        @csrf
        <div class="field">
            <x-input-label for="email" value="Alamat email" />
            <x-text-input id="email" class="auth-input" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="field-error" />
        </div>
        <x-primary-button class="button button-primary auth-submit">Kirim tautan reset</x-primary-button>
    </form>
    <p class="auth-switch"><a class="auth-link" href="{{ route('login') }}">← Kembali ke halaman masuk</a></p>
</x-guest-layout>
