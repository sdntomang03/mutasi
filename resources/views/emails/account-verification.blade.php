<x-mail::message>
# Verifikasi Alamat Email

Yth. {{ $userName }},

Terima kasih telah mendaftar di {{ $appName }}. Untuk mengaktifkan akun dan mulai menggunakan layanan, mohon verifikasi alamat email dengan menekan tombol di bawah ini.

<x-mail::button :url="$actionUrl">
Verifikasi Alamat Email
</x-mail::button>

Tautan verifikasi berlaku selama {{ $expiryMinutes }} menit. Jika sudah kedaluwarsa, Anda dapat meminta tautan baru setelah masuk ke aplikasi.

<x-mail::unofficial-notice />

Apabila Anda tidak merasa membuat akun, abaikan email ini. Tidak ada tindakan lebih lanjut yang diperlukan.

Hormat kami,  
Tim {{ $appName }}
</x-mail::message>
