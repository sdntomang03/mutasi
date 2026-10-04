<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $appName = config('app.name');

        VerifyEmail::toMailUsing(function (object $notifiable, string $url) use ($appName): MailMessage {
            $minutes = (int) config('auth.verification.expire', 60);

            return (new MailMessage)
                ->subject("Verifikasi Alamat Email Anda - {$appName}")
                ->greeting("Yth. {$notifiable->name},")
                ->line("Terima kasih telah mendaftar di {$appName}. Untuk mengaktifkan akun dan mulai menggunakan layanan, mohon verifikasi alamat email Anda dengan menekan tombol di bawah ini.")
                ->action('Verifikasi Alamat Email', $url)
                ->line("Tautan verifikasi berlaku selama {$minutes} menit. Jika sudah kedaluwarsa, Anda dapat meminta tautan baru setelah masuk ke aplikasi.")
                ->line('Apabila Anda tidak merasa membuat akun, abaikan email ini. Tidak ada tindakan lebih lanjut yang diperlukan.')
                ->salutation("Hormat kami,\nTim {$appName}");
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $token) use ($appName): MailMessage {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));
            $minutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

            return (new MailMessage)
                ->subject("Permintaan Atur Ulang Kata Sandi - {$appName}")
                ->greeting("Yth. {$notifiable->name},")
                ->line('Kami menerima permintaan untuk mengatur ulang kata sandi akun Anda.')
                ->action('Atur Ulang Kata Sandi', $url)
                ->line("Tautan ini berlaku selama {$minutes} menit.")
                ->line('Jika Anda tidak merasa meminta pengaturan ulang kata sandi, abaikan email ini; kata sandi Anda tidak akan berubah.')
                ->salutation("Hormat kami,\nTim {$appName}");
        });
    }
}
