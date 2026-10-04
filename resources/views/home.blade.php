@extends('layouts.exchange')

@section('title', 'Dashboard · Ruang Tukar Guru DKI')

@section('content')
<div class="page-shell">
    @include('partials.app-header')

    <main class="dashboard-main">
        <section class="dashboard-heading">
            <div>
                <p class="eyebrow">RUANG TUKAR GURU · DKI JAKARTA</p>
                <h1>Dashboard</h1>
                <p>Lihat calon tukeran yang cocok dengan rencana mutasimu.</p>
            </div>
            @if ($profile)
                <a class="button button-primary" href="{{ route('teacher-profile.edit') }}">Perbarui profil <span aria-hidden="true">→</span></a>
            @else
                <a class="button button-primary" href="{{ route('teacher-profile.edit') }}">Lengkapi profil <span aria-hidden="true">→</span></a>
            @endif
        </section>

        @if (! $profile)
            <section class="empty-state dashboard-empty">
                <span class="empty-symbol">↔</span>
                <h2>Lengkapi profil mutasimu</h2>
                <p>Setelah profil tersimpan, calon tukeran dengan kecocokan Sudin dua arah akan muncul di sini.</p>
                <a class="button button-primary" href="{{ route('teacher-profile.edit') }}">Buat profil</a>
            </section>
        @elseif (! $profile->destination_sudin_id || ! $profile->sudin_id || ! $profile->position || ! $profile->level || ($profile->position === 'guru_mapel' && ! $profile->subject_id) || ($profile->destination_position === 'guru_mapel' && $profile->destinationSubjects->isEmpty()) || ! $profile->destination_position || $profile->destinationLevels->isEmpty())
            <section class="empty-state dashboard-empty">
                <span class="empty-symbol">↔</span>
                <h2>Lengkapi profil mutasimu</h2>
                <p>Tambahkan Sudin, jabatan, dan jenjang asal serta tujuan pada profil untuk mulai menemukan calon tukeran.</p>
                <a class="button button-primary" href="{{ route('teacher-profile.edit') }}">Perbarui profil</a>
            </section>
        @else
            <section class="match-summary">
                <div><span>Sudin asal</span><strong>{{ $profile->sudin?->abbreviation ? $profile->sudin->abbreviation.' · ' : '' }}{{ $profile->sudin?->name }}</strong></div>
                <span class="summary-arrow" aria-hidden="true">↔</span>
                <div><span>Sudin tujuan</span><strong>{{ $profile->destinationSudin?->name }}</strong></div>
                <div><span>Jabatan · jenjang asal</span><strong>{{ \App\Models\TeacherProfile::POSITIONS[$profile->position] }} · {{ $profile->level }}{{ $profile->subject ? ' · '.$profile->subject->name : '' }}</strong></div>
                <div><span>Jabatan · jenjang tujuan</span><strong>{{ \App\Models\TeacherProfile::POSITIONS[$profile->destination_position] }} · {{ $profile->destinationLevels->pluck('level')->join(', ') }}{{ $profile->destination_position === 'guru_mapel' ? ' · '.$profile->destinationSubjects->pluck('name')->join(', ') : '' }}</strong></div>
            </section>

            <section class="panel dashboard-match-panel">
                <div class="panel-heading">
                    <div><p class="eyebrow">HASIL PENCARIAN</p><h2>Calon tukeran</h2></div>
                    <span class="match-icon" aria-hidden="true">↔</span>
                </div>
                <p class="muted-text">Calon ditampilkan jika Sudin, jabatan, dan jenjang asal serta tujuan kedua guru saling cocok.</p>
                <form method="GET" action="{{ route('dashboard') }}" class="dashboard-filter">
                    <label class="field" for="candidate-origin-district">
                        <span>Kecamatan asal calon (dalam Sudin tujuan)</span>
                        <select id="candidate-origin-district" name="candidate_origin_district_code" onchange="this.form.submit()">
                            <option value="">Semua kecamatan</option>
                            @foreach ($districts as $district)
                                <option value="{{ $district->code }}" @selected($selectedDistrictCode === $district->code)>{{ $district->name }} · {{ $district->regency_name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <noscript><button class="button button-secondary" type="submit">Terapkan filter</button></noscript>
                </form>

                @if ($matches->isEmpty())
                    <div class="empty-state dashboard-empty">
                        <span class="empty-symbol">⌁</span>
                        <h2>Belum ada kecocokan</h2>
                        <p>Belum ada guru dengan rencana mutasi yang cocok untuk wilayah ini. Kami akan memberi tahu melalui email saat kecocokan ditemukan.</p>
                    </div>
                @else
                    <p class="result-count">{{ $matches->count() }} calon tukeran cocok dengan profilmu</p>
                    <div class="dashboard-match-grid">
                        @foreach ($matches as $candidate)
                            @php($phoneDigits = preg_replace('/\D/', '', $candidate->phone))
                            <article class="match-card">
                                <div class="match-card-top">
                                    <div><p class="eyebrow">COCOK DUA ARAH</p><h3>{{ $candidate->name }}</h3></div>
                                    <span class="type-pill">{{ $candidate->employment_type }}</span>
                                </div>
                                <p class="match-school">{{ $candidate->school_name }} <span>·</span> {{ $candidate->sudin->abbreviation ?: $candidate->sudin->name }}</p>
                                <div class="match-facts">
                                    <p><span>Jabatan · jenjang</span>{{ \App\Models\TeacherProfile::POSITIONS[$candidate->position] }} · {{ $candidate->level }}{{ $candidate->subject ? ' · '.$candidate->subject->name : '' }}</p>
                                    <p><span>Mencari</span>{{ \App\Models\TeacherProfile::POSITIONS[$candidate->destination_position] }} · {{ $candidate->destinationLevels->pluck('level')->join(', ') }}{{ $candidate->destination_position === 'guru_mapel' ? ' · '.$candidate->destinationSubjects->pluck('name')->join(', ') : '' }}</p>
                                    <p><span>Asal sekolah</span>{{ $candidate->village_name }}, {{ $candidate->district_name }}, {{ $candidate->regency_name }}</p>
                                    <p><span>Alamat sekolah</span>{{ $candidate->school_address }}</p>
                                    <div class="match-destination-fact">
                                        <span>Tujuan mutasi</span>
                                        @if ($candidate->destinationDistricts->isEmpty())
                                            <strong>Semua kecamatan dalam {{ $candidate->destinationSudin->name }}</strong>
                                        @else
                                            <strong>{{ $candidate->destinationSudin->name }}</strong>
                                            <ul>
                                                @foreach ($candidate->destinationDistricts as $district)
                                                    <li>{{ $district->name }} · {{ $district->regency_name }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                </div>
                                <a class="contact-button" href="https://wa.me/{{ $phoneDigits }}" target="_blank" rel="noopener noreferrer"><span>Hubungi via WhatsApp</span><strong>{{ $candidate->phone }}</strong></a>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif
    @include('partials.mutation-flow', ['profile' => $profile, 'matchCount' => $matches->count()])
    </main>
    <footer class="footer"><span>Ruang Tukar Guru · DKI Jakarta</span><span>Kontak calon tukeran hanya tersedia setelah login</span></footer>
</div>
@endsection
