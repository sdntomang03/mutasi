<x-guest-layout>
    <div class="auth-heading">
        <p class="eyebrow">MULAI PERJALANANMU</p>
        <h2>Buat akun baru</h2>
        <p>Daftar untuk menemukan calon tukeran guru di DKI Jakarta.</p>
    </div>
    <form method="POST" action="{{ route('register') }}" class="auth-form">
        @csrf
        <div class="field">
            <x-input-label for="name" value="Nama lengkap" />
            <x-text-input id="name" class="auth-input" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="field-error" />
        </div>
        <div class="field">
            <x-input-label for="email" value="Alamat email" />
            <x-text-input id="email" class="auth-input" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="field-error" />
        </div>
        <div class="field">
            <x-input-label for="password" value="Kata sandi" />
            <x-text-input id="password" class="auth-input" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="field-error" />
        </div>
        <div class="field">
            <x-input-label for="password_confirmation" value="Ulangi kata sandi" />
            <x-text-input id="password_confirmation" class="auth-input" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="field-error" />
        </div>
        <x-primary-button class="button button-primary auth-submit">Buat akun <span aria-hidden="true">→</span></x-primary-button>
    </form>
    <p class="auth-switch">Sudah punya akun? <a class="auth-link" href="{{ route('login') }}">Masuk</a></p>
</x-guest-layout>
