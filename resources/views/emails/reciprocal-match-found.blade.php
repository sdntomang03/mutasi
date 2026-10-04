<x-mail::message>
# Calon Tukeran yang Cocok Ditemukan

Yth. {{ $teacherName }},

Kami menemukan guru lain yang rencana mutasinya saling sesuai dengan rencana Anda. Berikut ringkasannya:

<x-mail::panel>
**Calon tukeran:** {{ $matchedTeacherName }}  
**Sudin asal Anda:** {{ $originSudin }}  
**Sudin tujuan Anda:** {{ $destinationSudin }}
</x-mail::panel>

Kecocokan ditentukan secara dua arah berdasarkan Sudin, kecamatan, jabatan, dan jenjang pada profil masing-masing guru.

<x-mail::button :url="$dashboardUrl">
Lihat Calon Tukeran
</x-mail::button>

Demi menjaga privasi, email ini tidak memuat nomor telepon atau data kontak. Silakan masuk ke aplikasi untuk melihat detail dan menghubungi calon tukeran. Pastikan data profil Anda selalu terbarui, dan ubah status menjadi *Sudah mutasi* apabila proses tukeran telah selesai.

Hormat kami,  
Tim {{ config('app.name') }}
</x-mail::message>
