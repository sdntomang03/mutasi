<x-guest-layout>
    <div class="auth-heading">
        <p class="eyebrow">SELAMAT DATANG KEMBALI</p>
        <h2>Masuk ke akunmu</h2>
        <p>Masuk untuk melihat calon tukeran yang cocok.</p>
    </div>
    <x-auth-session-status class="auth-status" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="auth-form">
        @csrf
        <div class="field">
            <x-input-label for="email" value="Alamat email" />
            <x-text-input id="email" class="auth-input" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="field-error" />
        </div>
        <div class="field">
            <x-input-label for="password" value="Kata sandi" />
            <x-text-input id="password" class="auth-input" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="field-error" />
        </div>
        <div class="auth-form-options">
            <label class="auth-checkbox" for="remember_me">
                <input id="remember_me" type="checkbox" name="remember">
                <span>Ingat saya</span>
            </label>
            @if (Route::has('password.request'))
                <a class="auth-link" href="{{ route('password.request') }}">Lupa kata sandi?</a>
            @endif
        </div>
        <x-primary-button class="button button-primary auth-submit">Masuk</x-primary-button>
    </form>
    <p class="auth-switch">Belum punya akun? <a class="auth-link" href="{{ route('register') }}">Daftar sekarang</a></p>
</x-guest-layout>
