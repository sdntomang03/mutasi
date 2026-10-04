@extends('layouts.exchange')

@section('title', 'Daftar Guru · Ruang Tukar Guru DKI')

@section('content')
<div class="page-shell">
    @include('partials.app-header')
    <main class="dashboard-main">
        <section class="admin-intro">
            <p class="eyebrow">DAFTAR GURU</p>
            <h1>{{ $positionLabel ? $positionLabel.' lain yang ingin tukeran' : 'Daftar guru' }}</h1>
            <p>Daftar otomatis menampilkan guru dengan jabatan yang sama denganmu. Gunakan filter Sudin asal dan Sudin
                tujuan untuk mempersempit.</p>
        </section>

        @if (! $position)
        <section class="panel admin-users-panel">
            <div class="empty-state"><span class="empty-symbol">⌁</span>
                <p>Lengkapi <a href="{{ route('teacher-profile.edit') }}">profil mutasi</a> terlebih dahulu agar daftar
                    guru dengan jabatan yang sama dapat ditampilkan.</p>
            </div>
        </section>
        @else
        <section class="panel admin-users-panel">
            <div class="panel-heading">
                <div>
                    <p class="eyebrow">{{ strtoupper($positionLabel) }}</p>
                    <h2>Guru terdaftar</h2>
                </div><span class="step-number">{{ $teachers->count() }}</span>
            </div>
            <div class="dt-filters">
                <div class="dt-filter">
                    <label for="dt-origin">Sudin asal</label>
                    <select id="dt-origin">
                        <option value="">Semua Sudin asal</option>@foreach ($sudins as $sudin)<option
                            value="{{ $sudin->id }}">{{ $sudin->abbreviation ? $sudin->abbreviation.' · ' : '' }}{{
                            $sudin->name }}</option>@endforeach
                    </select>
                </div>
                <div class="dt-filter-arrow" aria-hidden="true">→</div>
                <div class="dt-filter">
                    <label for="dt-destination">Sudin tujuan</label>
                    <select id="dt-destination">
                        <option value="">Semua Sudin tujuan</option>@foreach ($sudins as $sudin)<option
                            value="{{ $sudin->id }}">{{ $sudin->abbreviation ? $sudin->abbreviation.' · ' : '' }}{{
                            $sudin->name }}</option>@endforeach
                    </select>
                </div>
                <button type="button" class="button button-secondary dt-reset" id="dt-reset">Reset filter</button>
            </div>
            <div class="dt-toolbar">
                <label class="dt-length"><span>Tampilkan</span>
                    <select id="dt-length">
                        <option>10</option>
                        <option>25</option>
                        <option>50</option>
                        <option>100</option>
                    </select>
                    <span>data per halaman</span>
                </label>
                <span class="dt-active-filters" id="dt-active" hidden></span>
            </div>
            <div class="dt-scroll">
                <table class="dt-table" id="teachers-table">
                    <thead>
                        <tr>
                            <th data-sort="0">Nama</th>
                            <th data-sort="1">Asal</th>
                            <th data-sort="2">Tujuan</th>
                            <th class="dt-nosort">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($teachers as $teacher)
                        @php
                        $originSubject = $teacher->position === 'guru_mapel' ? $teacher->subject?->name : null;
                        $destLevels = $teacher->destinationLevels->pluck('level')->join(', ');
                        $destSubjects = $teacher->destination_position === 'guru_mapel' ?
                        $teacher->destinationSubjects->pluck('name')->join(', ') : '';
                        $destDistricts = $teacher->destinationDistricts->pluck('name')->join(', ');
                        @endphp
                        <tr data-teacher-row data-origin="{{ $teacher->sudin_id }}"
                            data-destination="{{ $teacher->destination_sudin_id }}">
                            <td data-order="{{ strtolower($teacher->name) }}">
                                <strong>{{ $teacher->name }}</strong>
                                <small>{{ $teacher->school_name }}</small>
                            </td>
                            <td data-order="{{ strtolower($teacher->sudin?->abbreviation ?: $teacher->sudin?->name) }}">
                                <span class="dt-badge">{{ $teacher->sudin?->abbreviation ?: $teacher->sudin?->name
                                    }}</span>
                                <span class="dt-badge dt-badge-soft">{{ $teacher->employment_type }}</span>
                                <small>{{ $teacher->level }}{{ $originSubject ? ' · '.$originSubject : '' }} · {{
                                    $teacher->district_name }}</small>
                            </td>
                            <td
                                data-order="{{ strtolower($teacher->destinationSudin?->abbreviation ?: $teacher->destinationSudin?->name) }}">
                                <span class="dt-badge">{{ $teacher->destinationSudin?->abbreviation ?:
                                    $teacher->destinationSudin?->name ?: '-' }}</span>
                                <small>{{ $destLevels }}{{ $destSubjects ? ' · '.$destSubjects : '' }}{{ $destDistricts
                                    ? ' · '.$destDistricts : '' }}</small>
                            </td>
                            <td>
                                <button class="button button-secondary dt-detail-button" type="button"
                                    data-open-user="teacher-modal-{{ $teacher->id }}">Detail</button>
                                <template id="teacher-modal-{{ $teacher->id }}">
                                    <div class="teacher-detail">
                                        <p class="eyebrow">{{ strtoupper($positionLabel) }}</p>
                                        <h3>{{ $teacher->name }}</h3>
                                        <div class="teacher-detail-grid">
                                            <section>
                                                <h4>Asal</h4>
                                                <dl>
                                                    <dt>Sudin</dt>
                                                    <dd>{{
                                                        $teacher->sudin?->name }}</dd>
                                                    <dt>Status kepegawaian</dt>
                                                    <dd>{{ $teacher->employment_type }}</dd>
                                                    <dt>Jabatan</dt>
                                                    <dd>{{ $positionLabel }}</dd>
                                                    <dt>Jenjang</dt>
                                                    <dd>{{ $teacher->level }}</dd>
                                                    @if ($originSubject)<dt>Mata pelajaran</dt>
                                                    <dd>{{ $originSubject }}</dd>@endif
                                                    <dt>Wilayah</dt>
                                                    <dd>{{ $teacher->district_name }}, {{ $teacher->regency_name }}</dd>

                                                </dl>
                                            </section>
                                            <section>
                                                <h4>Tujuan</h4>
                                                <dl>
                                                    <dt>Sudin</dt>
                                                    <dd>{{
                                                        $teacher->destinationSudin?->name ?? '-' }}</dd>
                                                    <dt>Jabatan</dt>
                                                    <dd>{{ $teacher->destination_position ?
                                                        \App\Models\TeacherProfile::POSITIONS[$teacher->destination_position]
                                                        : '-' }}</dd>
                                                    <dt>Jenjang</dt>
                                                    <dd>{{ $destLevels ?: '-' }}</dd>
                                                    @if ($destSubjects)<dt>Mata pelajaran</dt>
                                                    <dd>{{ $destSubjects }}</dd>@endif
                                                    <dt>Kecamatan</dt>
                                                    <dd>{{ $destDistricts ?: 'Semua kecamatan di Sudin tujuan' }}</dd>
                                                </dl>
                                            </section>
                                        </div>
                                        <p class="field-help">Nomor kontak hanya tampil bila kamu dan guru ini saling
                                            cocok, di halaman dashboard.</p>
                                    </div>
                                </template>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="dt-empty" id="dt-empty" hidden>Tidak ada data yang cocok.</div>
            <div class="dt-footer">
                <p class="admin-pagination-summary" id="dt-info"></p>
                <div class="admin-pagination-links" id="dt-pages"></div>
            </div>
        </section>
        @endif
    </main>
    <footer class="footer"><span>Ruang Tukar Guru · Daftar guru</span><span>Nomor kontak hanya tampil pada kecocokan di
            dashboard</span></footer>
</div>
<div class="user-modal" id="user-modal" role="dialog" aria-modal="true" aria-label="Detail guru" hidden>
    <div class="user-modal-box"><button type="button" class="user-modal-close" id="user-modal-close"
            aria-label="Tutup">×</button>
        <div id="user-modal-body"></div>
    </div>
</div>
@if ($position)
<script src="{{ asset('teacher-directory.js').'?v='.filemtime(public_path('teacher-directory.js')) }}" defer></script>
@endif
@endsection