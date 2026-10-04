@extends('layouts.exchange')

@section('title', 'Pengaturan Sudin · Ruang Tukar Guru')

@section('content')
<div class="page-shell admin-shell">
    @include('partials.app-header')
    <main class="admin-main">
        <a class="back-link" href="{{ route('admin.users') }}">← Kembali ke daftar user</a>
        <section class="admin-intro"><p class="eyebrow">KONFIGURASI WILAYAH</p><h1>Atur cakupan Sudin</h1><p>Kelompokkan kecamatan ke Sudin yang menaunginya. Kecamatan hanya dapat dimasukkan ke satu Sudin.</p></section>
        <div class="admin-grid">
            <section class="panel sudin-form-panel">
                <div class="panel-heading"><div><p class="eyebrow">PENGATURAN SUDIN</p><h2 id="sudin-form-title">Tambah Sudin</h2></div></div>
                <form id="sudin-form">
                    <input type="hidden" id="sudin-id">
                    <label class="field"><span>Nama Sudin</span><input id="sudin-name" required maxlength="100" placeholder="Contoh: Jakarta Barat 2"></label>
                    <div class="district-select-heading"><div><h3>Pilih kecamatan</h3><p class="field-help">Pilih semua kecamatan dalam cakupan Sudin ini.</p></div><span class="fixed-pill" id="district-count">0 dipilih</span></div>
                    <div id="district-groups" class="district-groups"><div class="empty-state compact"><p>Memuat daftar kecamatan DKI Jakarta...</p></div></div>
                    <div class="form-actions"><button class="button button-primary" type="submit"><span>Simpan cakupan</span><span aria-hidden="true">→</span></button><button class="button button-ghost" id="cancel-sudin-edit" type="button" hidden>Batal</button></div>
                </form>
            </section>
            <aside class="panel sudin-list-panel">
                <div class="panel-heading"><div><p class="eyebrow">DAFTAR WILAYAH</p><h2>Sudin terdaftar</h2></div><span class="step-number" id="sudin-total">0</span></div>
                <div id="sudin-list" class="sudin-list"><div class="empty-state compact"><p>Memuat konfigurasi...</p></div></div>
            </aside>
        </div>
    </main>
    <footer class="footer"><span>Ruang Tukar Guru · Panel administrator</span><span>Gunakan data wilayah DKI Jakarta yang terbaru</span></footer>
</div>
<div class="toast" id="toast" role="status" aria-live="polite"></div>
<script src="{{ asset('admin.js') }}" defer></script>
@endsection
