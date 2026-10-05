@extends('layouts.exchange')

@section('title', 'Panduan · Ruang Tukar Guru DKI')

@section('content')
<div class="page-shell">
    @include('partials.app-header')
    <main class="profile-main guide-main">
        <a class="back-link" href="{{ route('dashboard') }}">← Kembali ke dashboard</a>
        <section class="admin-intro">
            <p class="eyebrow">PANDUAN PENGGUNA</p>
            <h1>Cara kerja Ruang Tukar Guru</h1>
            <p>Aplikasi ini mempertemukan guru DKI Jakarta yang ingin bertukar tempat tugas (tukeran). Halaman ini
                menjelaskan aturan pencocokan, status profil, dan notifikasi.</p>
        </section>

        <div class="guide-timeline" aria-label="Urutan penggunaan aplikasi">
            <section class="panel guide-card guide-flow-step">
                <span class="guide-step-marker" aria-hidden="true">01</span>
                <p class="eyebrow">LANGKAH 1</p>
                <h2>Daftar dan verifikasi email</h2>
                <ul>
                    <li>Daftar dengan nama, email, dan password, lalu klik tautan verifikasi yang dikirim ke email.</li>
                    <li>Akun yang belum terverifikasi tidak dapat membuka dashboard dan <strong>tidak ikut dalam
                            pencocokan</strong>.</li>
                    <li>Admin juga dapat memverifikasi email secara langsung bila email tidak sampai.</li>
                </ul>
            </section>

            <section class="panel guide-card guide-flow-step">
                <span class="guide-step-marker" aria-hidden="true">02</span>
                <p class="eyebrow">LANGKAH 2</p>
                <h2>Isi profil mutasi</h2>
                <ul>
                    <li><strong>Data diri:</strong> nama, nomor HP, dan jenis kepegawaian (PNS, PPPK, atau KKI).</li>
                    <li><strong>Jabatan dan jenjang:</strong> pilih jabatan (guru kelas atau guru mapel) dan
                        <em>satu</em> jenjang sekolah asal (SD, SMP, SMA, atau SMK). Guru mapel juga memilih <em>mapel
                            yang diampu</em>.</li>
                    <li><strong>Sekolah asal:</strong> nama sekolah, alamat, serta wilayah (provinsi selalu DKI Jakarta,
                        lalu kota, kecamatan, dan kelurahan).</li>
                    <li><strong>Sudin asal</strong> ditentukan otomatis dari kecamatan sekolah asal, berdasarkan
                        pengaturan admin.</li>
                    <li><strong>Tujuan mutasi:</strong> pilih Sudin tujuan, lalu pilih <em>semua kecamatan</em> atau
                        <em>beberapa kecamatan</em> di Sudin tersebut.</li>
                    <li><strong>Jabatan dan jenjang tujuan:</strong> guru mapel dapat memilih beberapa jenjang tujuan,
                        guru kelas hanya satu jenjang. Bila tujuanmu guru mapel, pilih satu atau beberapa mapel tujuan
                        (daftar mapel dikelola admin).</li>
                </ul>
            </section>

            <section class="panel guide-card guide-flow-step">
                <span class="guide-step-marker" aria-hidden="true">03</span>
                <p class="eyebrow">LANGKAH 3</p>
                <h2>Aturan pencocokan</h2>
                <p>Dua guru dianggap cocok bila <strong>saling menguntungkan</strong> (dua arah). Misalkan kamu
                    <em>A</em> dan calon tukeran <em>B</em>:</p>
                <ol>
                    <li>Sudin tujuan A adalah Sudin asal B, <strong>dan</strong> Sudin tujuan B adalah Sudin asal A.
                    </li>
                    <li>Jika A memilih kecamatan tertentu, kecamatan sekolah asal B harus termasuk di antaranya.</li>
                    <li>Jika B memilih kecamatan tertentu, kecamatan sekolah asal A harus termasuk di antaranya.</li>
                    <li>Jika tidak memilih kecamatan (semua kecamatan), syarat kecamatan pada sisi tersebut tidak
                        dibatasi.</li>
                    <li>Jabatan tujuan A sama dengan jabatan B saat ini, dan sebaliknya (guru kelas dengan guru kelas,
                        guru mapel dengan guru mapel).</li>
                    <li>Jenjang sekolah B termasuk dalam jenjang tujuan A, dan jenjang sekolah A termasuk dalam jenjang
                        tujuan B.</li>
                    <li>Untuk guru mapel: mapel yang diampu B termasuk dalam mapel tujuan A, dan mapel yang diampu A
                        termasuk dalam mapel tujuan B. Guru kelas tidak memakai mapel.</li>
                </ol>
                <p class="muted-text">Contoh: A guru kelas SD di Jakarta Barat 1 dan ingin ke Jakarta Timur 2 (kecamatan
                    Cakung) sebagai guru kelas SD. B guru kelas SD di Cakung dan ingin ke Jakarta Barat 1 sebagai guru
                    kelas SD. Keduanya cocok. Bila B berjabatan guru mapel atau berjenjang SMP, keduanya tidak cocok.
                </p>
                <p>Profil lama yang belum mengisi jabatan dan jenjang harus diperbarui lebih dulu. Profil
                    <strong>tidak</strong> diikutkan dalam pencocokan bila: email belum terverifikasi, status sudah
                    mutasi, ada pengajuan hapus yang menunggu admin, atau akun sudah dihapus.</p>
            </section>

            <section class="panel guide-card guide-flow-step">
                <span class="guide-step-marker" aria-hidden="true">04</span>
                <p class="eyebrow">DASHBOARD</p>
                <h2>Mencari tukeran</h2>
                <ul>
                    <li>Hasil pencocokan tampil di dashboard tanpa memuat ulang halaman.</li>
                    <li>Gunakan filter kecamatan asal untuk mempersempit calon.</li>
                    <li>Kontak (nomor HP) hanya terlihat pada calon yang cocok dua arah.</li>
                </ul>
            </section>

            <section class="panel guide-card guide-flow-step">
                <span class="guide-step-marker" aria-hidden="true">05</span>
                <p class="eyebrow">NOTIFIKASI</p>
                <h2>Email otomatis</h2>
                <ul>
                    <li>Saat kamu menyimpan atau memperbarui profil dan ditemukan pasangan cocok, <strong>kedua
                            guru</strong> menerima email.</li>
                    <li>Pasangan yang sama tidak diberi tahu berulang kali.</li>
                    <li>Bila email tidak masuk, periksa folder spam.</li>
                </ul>
            </section>

            <section class="panel guide-card guide-flow-step">
                <span class="guide-step-marker" aria-hidden="true">06</span>
                <p class="eyebrow">STATUS PROFIL</p>
                <h2>Sudah mutasi dan penghapusan profil</h2>
                <ol>
                    <li>Di halaman <a href="{{ route('teacher-profile.edit') }}">Profil</a>, ubah status menjadi
                        <strong>Sudah mutasi</strong> bila kamu sudah mendapat tukeran. Profilmu langsung keluar dari
                        pencocokan.</li>
                    <li>Setelah berstatus sudah mutasi, kamu dapat <strong>mengajukan penghapusan profil</strong>.
                        Pengajuan ini ditinjau admin.</li>
                    <li>Selama pengajuan menunggu, status tidak dapat dikembalikan ke belum mutasi.</li>
                    <li>Bila disetujui, akun dinonaktifkan (soft delete): tidak bisa login, tidak muncul di pencocokan,
                        dan tidak tampil di daftar admin.</li>
                    <li>Jika ingin aktif lagi sebelum pengajuan, ubah status kembali ke <strong>Belum mutasi</strong>
                        (hanya bila belum ada pengajuan yang menunggu).</li>
                </ol>
            </section>
        </div>

        <div class="guide-info-grid" aria-label="Informasi tambahan">
            <section class="panel guide-card guide-wide">
                <p class="eyebrow">TENTANG APLIKASI</p>
                <h2>Aplikasi unofficial</h2>
                <p>Ruang Tukar Guru dibuat <strong>secara mandiri dan swadaya</strong>. Aplikasi ini <strong>bukan
                        layanan resmi</strong> Dinas Pendidikan, Sudin, maupun instansi pemerintah lainnya. Hasil
                    pencocokan hanya membantu mempertemukan calon tukeran; proses dan keputusan mutasi tetap mengikuti
                    ketentuan resmi yang berlaku.</p>
            </section>

            <section class="panel guide-card guide-wide">
                <p class="eyebrow">PRIVASI</p>
                <h2>Data yang dibagikan</h2>
                <p>Data profilmu hanya ditampilkan kepada guru yang cocok dua arah dan kepada admin. Gunakan nomor HP
                    yang aktif agar calon tukeran dapat menghubungimu.</p>
            </section>
        </div>
    </main>
    <footer class="footer"><span>Ruang Tukar Guru · DKI Jakarta</span><span>Panduan pengguna</span></footer>
</div>
@endsection