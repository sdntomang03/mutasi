<x-guest-layout>
    <div class="auth-heading">
        <p class="eyebrow">VERIFIKASI KEAMANAN</p>
        <h2>Konfirmasi kata sandi</h2>
        <p>Ini area aman. Masukkan kata sandimu untuk melanjutkan.</p>
    </div>
    <form method="POST" action="{{ route('password.confirm') }}" class="auth-form">
        @csrf
        <div class="field">
            <x-input-label for="password" value="Kata sandi" />
            <x-text-input id="password" class="auth-input" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="field-error" />
        </div>
        <x-primary-button class="button button-primary auth-submit">Konfirmasi</x-primary-button>
    </form>
</x-guest-layout>
