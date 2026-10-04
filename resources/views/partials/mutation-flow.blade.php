@php
    $profileReady = $profile && $profile->destination_sudin_id && $profile->destination_position;
    $found = $matchCount > 0;
    $steps = [
        ['title' => 'Cari Kecocokan', 'desc' => 'Temukan guru yang ingin bertukar tempat dengan Anda.',
            'status' => $found ? 'completed' : ($profileReady ? 'in_progress' : 'action_required'),
            'note' => $found ? $matchCount.' guru cocok ditemukan.' : ($profileReady ? 'Belum ada kecocokan. Coba tambah pilihan kecamatan.' : 'Lengkapi profil mutasi terlebih dahulu.'),
            'cta' => $found ? null : ($profileReady ? 'Ubah Tujuan Mutasi' : 'Lengkapi Profil'), 'href' => route('teacher-profile.edit')],
        ['title' => 'Ajukan & Siapkan Berkas', 'desc' => 'Hubungi calon tukeran, sepakati rencana, lalu siapkan dokumen persyaratan.',
            'status' => $found ? 'in_progress' : 'pending', 'note' => null, 'cta' => $found ? 'Baca Panduan' : null, 'href' => route('guide')],
        ['title' => 'Persetujuan Kepala Sekolah', 'desc' => 'Kepala sekolah asal dan tujuan menyetujui rencana tukar tempat.', 'status' => 'pending'],
        ['title' => 'Verifikasi Sudin', 'desc' => 'Seksi PTK Sudin Pendidikan memeriksa kelengkapan berkas.', 'status' => 'pending'],
        ['title' => 'Validasi Dinas & SK BKD', 'desc' => 'Dinas Pendidikan memvalidasi dan SK mutasi diterbitkan.', 'status' => 'pending'],
        ['title' => 'SPMT & Dapodik', 'desc' => 'Lapor mulai bertugas di sekolah baru dan pastikan data Dapodik terbarui.', 'status' => 'pending'],
    ];
    $labels = ['completed' => 'Selesai', 'in_progress' => 'Sedang berjalan', 'action_required' => 'Perlu tindakan', 'pending' => 'Menunggu'];
@endphp
<section class="panel flow-card" aria-labelledby="flow-title">
    <div class="flow-head">
        <p class="eyebrow">PANDUAN ALUR</p>
        <h2 id="flow-title">Alur mutasi guru</h2>
        <p>Ilustrasi alur umum. Tahap 2–6 dilakukan di luar aplikasi dan mengikuti ketentuan resmi Dinas Pendidikan.</p>
    </div>
    <ol class="flow-steps">
        @foreach ($steps as $step)
            <li class="flow-step flow-{{ $step['status'] }}" @if (in_array($step['status'], ['in_progress', 'action_required'], true)) aria-current="step" @endif>
                <span class="flow-dot" aria-hidden="true">@if ($step['status'] === 'completed')✓@elseif ($step['status'] === 'action_required')!@else{{ $loop->iteration }}@endif</span>
                <div class="flow-body">
                    <span class="flow-badge">{{ $labels[$step['status']] }}</span>
                    <h3>{{ $step['title'] }}</h3>
                    <p>{{ $step['desc'] }}</p>
                    @if (! empty($step['note']))<p class="flow-note">{{ $step['note'] }}</p>@endif
                    @if (! empty($step['cta']))<a class="flow-cta" href="{{ $step['href'] }}">{{ $step['cta'] }} <span aria-hidden="true">→</span></a>@endif
                </div>
            </li>
        @endforeach
    </ol>
</section>