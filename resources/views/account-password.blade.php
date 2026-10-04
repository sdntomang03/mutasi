@extends('layouts.exchange')

@section('title', 'Ganti Password · Ruang Tukar Guru DKI')

@section('content')
<div class="page-shell">
    @include('partials.app-header')
    <main class="profile-main">
        <section class="dashboard-heading">
            <div>
                <p class="eyebrow">KEAMANAN AKUN</p>
                <h1>Ganti password</h1>
                <p>Perbarui password agar akunmu tetap aman.</p>
            </div>
        </section>
        <div class="password-layout">
            <section class="panel password-panel">
                @if (session('status') === 'password-updated')
                    <p class="form-success" role="status">Password berhasil diganti.</p>
                @endif
                <form method="POST" action="{{ route('password.update') }}" novalidate>
                    @csrf
                    @method('PUT')
                    @foreach ([
                        ['current_password', 'Password saat ini', 'current-password'],
                        ['password', 'Password baru', 'new-password'],
                        ['password_confirmation', 'Ulangi password baru', 'new-password'],
                    ] as [$name, $label, $autocomplete])
                        <div class="field password-field">
                            <label for="{{ $name }}">{{ $label }}</label>
                            <div class="password-input">
                                <input id="{{ $name }}" type="password" name="{{ $name }}" autocomplete="{{ $autocomplete }}" required @class(['is-invalid' => $errors->updatePassword->has($name)])>
                                <button type="button" class="password-toggle" data-toggle-password="{{ $name }}" aria-label="Tampilkan password" aria-pressed="false">Lihat</button>
                            </div>
                            @foreach ($errors->updatePassword->get($name) as $message)
                                <small class="form-error">{{ $message }}</small>
                            @endforeach
                        </div>
                    @endforeach
                    <div class="form-actions">
                        <button class="button button-primary" type="submit"><span>Simpan password</span><span aria-hidden="true">→</span></button>
                    </div>
                </form>
            </section>
            <aside class="panel password-tips">
                <h2>Tips password aman</h2>
                <ul>
                    <li>Minimal 8 karakter, lebih panjang lebih baik.</li>
                    <li>Gabungkan huruf besar, huruf kecil, angka, dan simbol.</li>
                    <li>Jangan memakai password yang sama dengan akun lain.</li>
                    <li>Jika password pernah direset admin menjadi <code>password</code>, segera ganti di sini.</li>
                </ul>
            </aside>
        </div>
    </main>
</div>
<script>
    document.querySelectorAll('[data-toggle-password]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.togglePassword);
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.textContent = show ? 'Sembunyi' : 'Lihat';
            button.setAttribute('aria-pressed', show);
        });
    });
</script>
@endsection