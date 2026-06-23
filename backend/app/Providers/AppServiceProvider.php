<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // The password-reset link points to the SPA, which then calls the API.
        ResetPassword::createUrlUsing(function (CanResetPassword $notifiable, string $token): string {
            $frontend = rtrim((string) config('app.frontend_url'), '/');
            $email = urlencode($notifiable->getEmailForPasswordReset());

            return "{$frontend}/reset-password?token={$token}&email={$email}";
        });
    }
}
