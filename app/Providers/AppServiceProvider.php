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
                ->markdown('emails.account-verification', [
                    'userName' => $notifiable->name,
                    'appName' => $appName,
                    'actionUrl' => $url,
                    'expiryMinutes' => $minutes,
                ]);
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $token) use ($appName): MailMessage {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));
            $minutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

            return (new MailMessage)
                ->subject("Permintaan Atur Ulang Kata Sandi - {$appName}")
                ->markdown('emails.password-reset', [
                    'userName' => $notifiable->name,
                    'appName' => $appName,
                    'actionUrl' => $url,
                    'expiryMinutes' => $minutes,
                ]);
        });
    }
}
