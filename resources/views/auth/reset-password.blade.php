<x-guest-layout>
    <div class="auth-heading">
        <p class="eyebrow">PEMULIHAN AKUN</p>
        <h2>Buat kata sandi baru</h2>
        <p>Pilih kata sandi baru untuk mengamankan akunmu.</p>
    </div>
    <form method="POST" action="{{ route('password.store') }}" class="auth-form">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div class="field">
            <x-input-label for="email" value="Alamat email" />
            <x-text-input id="email" class="auth-input" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="field-error" />
        </div>
        <div class="field">
            <x-input-label for="password" value="Kata sandi baru" />
            <x-text-input id="password" class="auth-input" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="field-error" />
        </div>
        <div class="field">
            <x-input-label for="password_confirmation" value="Ulangi kata sandi baru" />
            <x-text-input id="password_confirmation" class="auth-input" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="field-error" />
        </div>
        <x-primary-button class="button button-primary auth-submit">Simpan kata sandi</x-primary-button>
    </form>
</x-guest-layout>
