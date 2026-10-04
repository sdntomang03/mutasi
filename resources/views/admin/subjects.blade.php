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

        <section class="panel admin-users-panel">
            <div class="panel-heading"><div><p class="eyebrow">MAPEL</p><h2>Kelola mapel</h2></div><span class="step-number" id="subject-count">0</span></div>
            <form id="subject-form" class="deletion-request-actions" style="margin-bottom:16px">
                <input name="name" required minlength="2" maxlength="100" placeholder="Nama mapel baru" aria-label="Nama mapel baru">
                <button class="button button-primary" type="submit">Tambah mapel</button>
            </form>
            <form id="subject-import-form" class="deletion-request-actions" style="margin-bottom:16px">
                <input type="file" name="file" accept=".csv,.txt" required aria-label="File CSV mapel">
                <button class="button button-secondary" type="submit">Impor CSV</button>
                <small class="field-help">Satu mapel per baris pada kolom pertama (baris judul "nama" opsional). Mapel yang sudah ada dilewati.</small>
            </form>
            <div class="admin-users-list" id="subject-list"></div>
        </section>
    </main>
</div>
<div class="toast" id="toast" role="status" aria-live="polite"></div>
<script src="{{ asset('admin-subjects.js') }}?v={{ filemtime(public_path('admin-subjects.js')) }}" defer></script>
@endsection