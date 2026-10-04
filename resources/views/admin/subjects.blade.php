@extends('layouts.exchange')

@section('title', 'Mata Pelajaran · Ruang Tukar Guru')

@section('content')
<div class="page-shell admin-shell">
    @include('partials.app-header')
    <main class="admin-main">
        <section class="admin-intro">
            <p class="eyebrow">DATA MASTER</p>
            <h1>Mata pelajaran</h1>
            <p>Daftar mapel yang dapat dipilih guru mapel sebagai mapel yang diampu dan mapel tujuan. Mapel yang sudah dipakai profil guru tidak dapat dihapus.</p>
        </section>

        <div class="subject-layout">
            <section class="panel subject-tools">
                <div class="panel-heading"><div><p class="eyebrow">TAMBAH MAPEL</p><h2>Mapel baru</h2></div></div>
                <form id="subject-form" class="subject-form">
                    <label class="field"><span>Nama mapel</span><input name="name" required minlength="2" maxlength="100" placeholder="Contoh: Matematika"></label>
                    <button class="button button-primary" type="submit">Tambah mapel</button>
                </form>

                <div class="section-divider"></div>
                <div class="panel-heading"><div><p class="eyebrow">IMPOR</p><h2>Dari file CSV</h2></div></div>
                <form id="subject-import-form" class="subject-form">
                    <label class="field"><span>File CSV</span><input class="subject-file" type="file" name="file" accept=".csv,.txt" required></label>
                    <small class="field-help">Satu mapel per baris pada kolom pertama. Baris judul "nama" boleh ada. Mapel yang sudah ada otomatis dilewati.</small>
                    <button class="button button-secondary" type="submit">Impor CSV</button>
                </form>
            </section>

            <section class="panel subject-list-panel">
                <div class="panel-heading"><div><p class="eyebrow">DAFTAR</p><h2>Mapel tersedia</h2></div><span class="step-number" id="subject-count">0</span></div>
                <label class="field subject-search"><span class="sr-only">Cari mapel</span><input type="search" id="subject-search" placeholder="Cari mapel..." autocomplete="off"></label>
                <div class="subject-list" id="subject-list"></div>
                <p class="subject-empty" id="subject-empty" hidden>Belum ada mapel.</p>
            </section>
        </div>
    </main>
</div>
<div class="toast" id="toast" role="status" aria-live="polite"></div>
<script src="{{ asset('admin-subjects.js') }}?v={{ filemtime(public_path('admin-subjects.js')) }}" defer></script>
@endsection