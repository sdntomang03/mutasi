<x-mail::message>
# Atur Ulang Kata Sandi

Yth. {{ $userName }},

Kami menerima permintaan untuk mengatur ulang kata sandi akun Anda. Tekan tombol di bawah ini untuk membuat kata sandi baru.

<x-mail::button :url="$actionUrl">
Atur Ulang Kata Sandi
</x-mail::button>

Tautan ini berlaku selama {{ $expiryMinutes }} menit.

<x-mail::unofficial-notice />

Jika Anda tidak merasa meminta pengaturan ulang kata sandi, abaikan email ini; kata sandi Anda tidak akan berubah.

Hormat kami,  
Tim {{ $appName }}
</x-mail::message>
